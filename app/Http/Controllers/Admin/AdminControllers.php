<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Trip, User, Vehicle, FuelRequest, DriverPayment, MaintenanceRecord, TrainingAssignment, DriverAward};
use App\Services\{FuelDepletionService, PaymentService, AwardService};
use Illuminate\Http\Request;

// ═══════════════════════════════════════════════════════════════════
// AdminDashboardController
// ═══════════════════════════════════════════════════════════════════
class AdminDashboardController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private AwardService $awardService,
    ) {}

    public function index()
    {
        $stats = [
            'trips_today'       => Trip::today()->count(),
            'pending_approvals' => Trip::pending()->count(),
            'fuel_requests'     => FuelRequest::where('status', 'pending')->count(),
            'critical_vehicles' => Vehicle::where('fuel_status', 'critical')->orWhere('fuel_status', 'empty')->count(),
            'total_outstanding' => $this->paymentService->getOutstandingSummary()->sum('amount_due'),
            'drivers_count'     => User::where('role', 'driver')->count(),
            'active_vehicles'   => Vehicle::where('status', 'active')->count(),
            'overdue_services'  => MaintenanceRecord::where('status', 'overdue')->count(),
        ];

        $pendingTrips    = Trip::pending()->with(['bookedBy', 'passenger', 'driver', 'vehicle'])->latest()->take(10)->get();
        $todayTrips      = Trip::today()->with(['passenger', 'driver', 'vehicle'])->latest()->get();
        $fuelAlerts      = Vehicle::whereIn('fuel_status', ['critical', 'empty'])->with('assignedDriver')->get();
        $monthlyLeader   = $this->awardService->getMonthlyLeaderboard()->take(3);

        return view('admin.dashboard', compact('stats', 'pendingTrips', 'todayTrips', 'fuelAlerts', 'monthlyLeader'));
    }
}

// ═══════════════════════════════════════════════════════════════════
// TripController (Admin)
// ═══════════════════════════════════════════════════════════════════
class TripController extends Controller
{
    public function index(Request $request)
    {
        $trips = Trip::with(['bookedBy', 'passenger', 'driver', 'vehicle'])
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->when($request->search,  fn($q) => $q->whereHas('passenger', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->when($request->date,    fn($q) => $q->whereDate('scheduled_at', $request->date))
            ->latest()
            ->paginate(20);

        $drivers  = User::where('role', 'driver')->where('is_active', true)->get();
        $vehicles = Vehicle::where('status', 'active')->with('assignedDriver')->get();
        $staff    = User::whereIn('role', ['staff', 'marketer', 'admin'])->where('is_active', true)->get();

        return view('admin.trips.index', compact('trips', 'drivers', 'vehicles', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'passenger_id' => 'required|exists:users,id',
            'driver_id'    => 'required|exists:users,id',
            'vehicle_id'   => 'required|exists:vehicles,id',
            'reason'       => 'required|string|min:5',
            'destination'  => 'nullable|string',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $trip = Trip::create([
            ...$validated,
            'booked_by_id' => auth()->id(),
            'status'       => 'pending',
        ]);

        // Notify driver
        $trip->driver->notify(new \App\Notifications\TripRequestNotification($trip));

        return redirect()->route('admin.trips.index')
            ->with('success', "Trip #{$trip->trip_code} booked and driver notified.");
    }

    public function approve(Trip $trip)
    {
        abort_if(!$trip->isPending(), 403, 'Trip is not pending approval.');

        $trip->update([
            'status'       => 'approved',
            'approved_by_id' => auth()->id(),
            'approved_at'  => now(),
        ]);

        // Update driver status
        optional($trip->driver->driverProfile)->update(['status' => 'on_trip']);

        // Notify driver and passenger
        $trip->driver->notify(new \App\Notifications\TripApprovedNotification($trip));

        return back()->with('success', "Trip #{$trip->trip_code} approved.");
    }

    public function reject(Request $request, Trip $trip)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        $trip->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by_id'   => auth()->id(),
            'approved_at'      => now(),
        ]);

        $trip->driver->notify(new \App\Notifications\TripRejectedNotification($trip));

        return back()->with('success', "Trip #{$trip->trip_code} rejected.");
    }

    public function show(Trip $trip)
    {
        $trip->load(['bookedBy', 'passenger', 'driver', 'vehicle', 'locations', 'rating', 'approvedBy']);
        return view('admin.trips.show', compact('trip'));
    }
}

// ═══════════════════════════════════════════════════════════════════
// DriverController (Admin manages drivers)
// ═══════════════════════════════════════════════════════════════════
class DriverController extends Controller
{
    public function index()
    {
        $drivers = User::where('role', 'driver')
            ->with(['driverProfile', 'assignedVehicle', 'ratings'])
            ->withCount(['drivenTrips as completed_trips' => fn($q) => $q->where('status', 'completed')])
            ->get()
            ->map(function ($driver) {
                $driver->avg_rating = round($driver->ratings->avg('rating'), 1);
                $driver->outstanding = $driver->outstanding_payment;
                return $driver;
            });

        return view('admin.drivers.index', compact('drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users',
            'phone'           => 'required|string',
            'employee_id'     => 'nullable|string|unique:users',
            'license_number'  => 'required|string|unique:driver_profiles',
            'license_expiry'  => 'required|date',
            'license_class'   => 'required|in:A,B,C,D,E',
            'years_experience'=> 'required|integer|min:0',
            'per_trip_rate'   => 'required|numeric|min:0',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'phone'       => $validated['phone'],
            'employee_id' => $validated['employee_id'] ?? null,
            'role'        => 'driver',
            'password'    => bcrypt('nsia@driver123'), // default password
        ]);

        $user->driverProfile()->create([
            'license_number'   => $validated['license_number'],
            'license_expiry'   => $validated['license_expiry'],
            'license_class'    => $validated['license_class'],
            'years_experience' => $validated['years_experience'],
            'per_trip_rate'    => $validated['per_trip_rate'],
        ]);

        return redirect()->route('admin.drivers.index')
            ->with('success', "Driver {$user->name} created. Default password: nsia@driver123");
    }

    public function show(User $driver)
    {
        abort_if($driver->role !== 'driver', 404);
        $driver->load(['driverProfile', 'assignedVehicle', 'trainingAssignments', 'awards', 'payments']);

        $recentTrips   = $driver->drivenTrips()->with('passenger', 'vehicle')->latest()->take(10)->get();
        $recentRatings = $driver->ratings()->with('ratedBy', 'trip')->latest()->take(10)->get();

        return view('admin.drivers.show', compact('driver', 'recentTrips', 'recentRatings'));
    }

    public function updateStatus(Request $request, User $driver)
    {
        $request->validate(['status' => 'required|in:available,on_trip,off_duty,training,suspended']);
        $driver->driverProfile()->update(['status' => $request->status]);
        return back()->with('success', 'Driver status updated.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// VehicleController (Admin)
// ═══════════════════════════════════════════════════════════════════
class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('assignedDriver')
            ->withCount('trips')
            ->orderBy('type')
            ->get();

        $drivers = User::where('role', 'driver')
            ->where('is_active', true)
            ->doesntHave('assignedVehicle')
            ->get();

        return view('admin.vehicles.index', compact('vehicles', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'make'                       => 'required|string',
            'model'                      => 'required|string',
            'year'                       => 'required|digits:4',
            'plate_number'               => 'required|string|unique:vehicles',
            'type'                       => 'required|in:staff_bus,marketing_car,executive_car,other',
            'engine_size'                => 'required|numeric|min:0.5',
            'tank_capacity'              => 'required|numeric|min:10',
            'fuel_consumption_per_hour'  => 'required|numeric|min:0.1',
            'assigned_driver_id'         => 'nullable|exists:users,id',
        ]);

        Vehicle::create($validated);

        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle added to fleet.');
    }

    public function assignDriver(Request $request, Vehicle $vehicle)
    {
        $request->validate(['driver_id' => 'required|exists:users,id']);
        $vehicle->update(['assigned_driver_id' => $request->driver_id]);
        return back()->with('success', 'Driver assigned to vehicle.');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['assignedDriver', 'fuelLogs', 'maintenanceRecords', 'trips']);
        $fuelHistory = $vehicle->fuelSnapshots()->latest('recorded_at')->take(24)->get();
        return view('admin.vehicles.show', compact('vehicle', 'fuelHistory'));
    }
}

// ═══════════════════════════════════════════════════════════════════
// FuelController (Admin)
// ═══════════════════════════════════════════════════════════════════
class FuelController extends Controller
{
    public function __construct(private FuelDepletionService $fuelService) {}

    public function index()
    {
        $requests = FuelRequest::where('status', 'pending')
            ->with(['vehicle', 'driver'])
            ->latest()
            ->get();

        $vehicles = Vehicle::with(['assignedDriver', 'fuelLogs'])
            ->where('status', 'active')
            ->orderBy('current_fuel_level')
            ->get()
            ->map(function ($v) {
                $dep = $this->fuelService->calculateDepletion($v);
                $v->hours_remaining    = $dep['hours_remaining'];
                $v->estimated_empty_at = $dep['estimated_empty_at'];
                $v->fuel_percent       = $dep['percent'];
                return $v;
            });

        return view('admin.fuel.index', compact('requests', 'vehicles'));
    }

    public function acknowledge(FuelRequest $fuelRequest)
    {
        $fuelRequest->update([
            'status'              => 'acknowledged',
            'acknowledged_by_id'  => auth()->id(),
            'acknowledged_at'     => now(),
        ]);

        $fuelRequest->driver->notify(new \App\Notifications\FuelAcknowledgedNotification($fuelRequest));

        return back()->with('success', 'Fuel request acknowledged. Driver notified.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// PaymentController (Admin)
// ═══════════════════════════════════════════════════════════════════
class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index()
    {
        $isWeekend   = $this->paymentService->isPaymentWindow();
        $outstanding = $this->paymentService->getOutstandingSummary();
        $history     = DriverPayment::with(['driver', 'processedBy'])->latest()->paginate(20);

        return view('admin.payments.index', compact('isWeekend', 'outstanding', 'history'));
    }

    public function pay(Request $request, User $driver)
    {
        try {
            $payment = $this->paymentService->processPayment($driver, auth()->user(), $request->reference);
            return back()->with('success', "₦" . number_format($payment->total_amount) . " paid to {$driver->name}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function payAll()
    {
        try {
            $payments = $this->paymentService->processAllPayments(auth()->user());
            $total    = collect($payments)->sum('total_amount');
            return back()->with('success', count($payments) . " drivers paid. Total: ₦" . number_format($total));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// MaintenanceController (Admin)
// ═══════════════════════════════════════════════════════════════════
class MaintenanceController extends Controller
{
    public function index()
    {
        $records = MaintenanceRecord::with(['vehicle', 'loggedBy'])
            ->orderByDesc('service_date')
            ->paginate(20);

        // Mark overdue records
        MaintenanceRecord::where('status', 'scheduled')
            ->whereDate('next_service_date', '<', today())
            ->update(['status' => 'overdue']);

        $vehicles = Vehicle::where('status', 'active')->get();

        return view('admin.maintenance.index', compact('records', 'vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id'         => 'required|exists:vehicles,id',
            'service_type'       => 'required|string',
            'service_date'       => 'required|date',
            'next_service_date'  => 'nullable|date|after:service_date',
            'mileage_at_service' => 'nullable|integer',
            'provider'           => 'nullable|string',
            'cost'               => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
        ]);

        MaintenanceRecord::create([
            ...$validated,
            'status'       => 'completed',
            'logged_by_id' => auth()->id(),
        ]);

        // Update vehicle's last/next service dates
        Vehicle::where('id', $validated['vehicle_id'])->update([
            'last_service_date' => $validated['service_date'],
            'next_service_date' => $validated['next_service_date'] ?? null,
        ]);

        return back()->with('success', 'Maintenance record saved.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// TrainingController (Admin)
// ═══════════════════════════════════════════════════════════════════
class TrainingController extends Controller
{
    public function index()
    {
        $assignments = TrainingAssignment::with(['driver', 'assignedBy'])
            ->orderByDesc('training_date')
            ->paginate(20);

        $drivers = User::where('role', 'driver')->where('is_active', true)->get();

        return view('admin.training.index', compact('assignments', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id'     => 'required|exists:users,id',
            'training_type' => 'required|string',
            'provider'      => 'nullable|string',
            'training_date' => 'required|date',
            'duration_days' => 'required|integer|min:1',
            'notes'         => 'nullable|string',
        ]);

        $assignment = TrainingAssignment::create([
            ...$validated,
            'assigned_by_id' => auth()->id(),
            'status'         => 'upcoming',
        ]);

        // Update driver status
        optional($assignment->driver->driverProfile)->update(['status' => 'training']);

        // Notify driver
        $assignment->driver->notify(new \App\Notifications\TrainingAssignedNotification($assignment));

        return back()->with('success', 'Training assigned. Driver notified.');
    }

    public function updateStatus(Request $request, TrainingAssignment $assignment)
    {
        $request->validate(['status' => 'required|in:upcoming,in_progress,completed,cancelled']);
        $assignment->update(['status' => $request->status]);

        // If completed, set driver back to available
        if ($request->status === 'completed') {
            optional($assignment->driver->driverProfile)->update(['status' => 'available']);
        }

        return back()->with('success', 'Training status updated.');
    }
}

// ═══════════════════════════════════════════════════════════════════
// AwardController (Admin)
// ═══════════════════════════════════════════════════════════════════
class AwardController extends Controller
{
    public function __construct(private AwardService $awardService) {}

    public function index()
    {
        $leaderboard = $this->awardService->getMonthlyLeaderboard();
        $monthlyWinner = DriverAward::where('award_type', 'driver_of_month')
            ->where('year', now()->year)->where('month', now()->month)
            ->with('driver')->first();

        $yearlyAwards = DriverAward::where('award_type', 'driver_of_year')
            ->with('driver')->orderByDesc('year')->get();

        $monthlyHistory = DriverAward::where('award_type', 'driver_of_month')
            ->where('year', now()->year)->with('driver')
            ->orderBy('month')->get();

        return view('admin.awards.index', compact(
            'leaderboard', 'monthlyWinner', 'yearlyAwards', 'monthlyHistory'
        ));
    }

    public function computeMonth(Request $request)
    {
        $award = $this->awardService->calculateMonthlyAward(
            $request->year ?? now()->year,
            $request->month ?? now()->month
        );

        return back()->with('success', $award
            ? "Driver of the Month: {$award->driver->name}"
            : 'No eligible drivers this month.');
    }
}
