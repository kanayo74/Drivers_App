<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\{Trip, TripLocation, MonthlyMaintenanceLog, VehicleMaintenanceNeed, User};
use App\Notifications\{RateDriverNotification, MaintenanceNeedAlert, MonthlyMaintenanceDoneNotification};
use App\Services\WeekendPaymentService;
use Illuminate\Http\Request;

class DriverTripController extends Controller
{
    public function index(Request $request)
    {
        $driver = auth()->user();
        $rate   = $driver->driverProfile?->per_trip_rate ?? 1200;

        $trips = Trip::where('driver_id', $driver->id)
            ->with(['passenger', 'vehicle', 'rating'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->month,  fn($q) => $q->whereMonth('scheduled_at', $request->month))
            ->when($request->search, fn($q) => $q->where(function ($i) use ($request) {
                $i->where('trip_code', 'like', "%{$request->search}%")
                  ->orWhere('reason', 'like', "%{$request->search}%")
                  ->orWhereHas('passenger', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            }))
            ->latest('scheduled_at')->paginate(15);

        $stats = [
            'total_completed' => Trip::where('driver_id', $driver->id)->where('status','completed')->count(),
            'this_month'      => Trip::where('driver_id', $driver->id)->where('status','completed')->whereMonth('completed_at', now()->month)->count(),
            'avg_rating'      => round($driver->ratings()->avg('rating') ?? 0, 1),
            'total_ratings'   => $driver->ratings()->count(),
            'total_earned'    => Trip::where('driver_id', $driver->id)->where('status','completed')->where('qualifies_for_payment', true)->count() * $rate,
            'weekend_trips'   => Trip::where('driver_id', $driver->id)->where('status','completed')->where('qualifies_for_payment', true)->count(),
        ];

        return view('driver.trips.index', compact('trips', 'stats'));
    }

    public function start(Trip $trip)
    {
        abort_if($trip->driver_id !== auth()->id(), 403);
        abort_if($trip->status !== 'approved', 422, 'Trip is not ready to start.');
        
        if (Trip::where('driver_id', auth()->id())->where('status', 'in_progress')->exists()) {
            return back()->with('error', 'You already have a trip in progress. Complete it first.');
        }
        
        $trip->update(['status' => 'in_progress', 'started_at' => now()]);
        optional(auth()->user()->driverProfile)->update(['status' => 'on_trip']);
        
        return back()->with('success', "Trip #{$trip->trip_code} started. Safe journey!");
    }

    public function complete(Trip $trip)
    {
        $driver = auth()->user();

        // ensure the authenticated driver owns this trip
        abort_if($trip->driver_id !== $driver->id, 403, 'Unauthorized.');

        // If already completed, return a friendly response
        if ($trip->status === 'completed') {
            return back()->with('info', 'Trip already completed.');
        }

        // Accept several common "in progress" statuses to avoid false negatives
        $allowedInProgress = ['in_progress', 'ongoing', 'started', 'active', 'en_route'];
        if (!in_array($trip->status, $allowedInProgress, true)) {
            return back()->withErrors([
                'trip' => "Trip is not in progress. Current status: {$trip->status}"
            ]);
        }

        // Update trip status
        $trip->status = 'completed';
        $trip->completed_at = now();
        $trip->save();

        // Update driver profile status
        optional($driver->driverProfile)->update(['status' => 'available']);
        
        // Mark payment eligibility
        app(WeekendPaymentService::class)->markTripPaymentEligibility($trip->fresh());
        
        // Notify passenger to rate the driver
        $trip->passenger->notify(new RateDriverNotification($trip));
        
        // Handle monthly maintenance log
        $vehicle = $trip->vehicle;
        if ($vehicle) {
            MonthlyMaintenanceLog::firstOrCreate(
                ['vehicle_id' => $vehicle->id, 'month' => now()->month, 'year' => now()->year],
                ['driver_id' => $driver->id, 'log_date' => today(), 'status' => 'pending']
            );
        }
        
        $qualifies = $trip->fresh()->qualifies_for_payment;
        
        // Return JSON response for API requests, or redirect for web requests
        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Trip marked as completed.',
                'trip' => $trip,
            ], 200);
        }
        
        return back()->with('success',
            "Trip #{$trip->trip_code} completed!" .
            ($qualifies ? " ✓ This trip qualifies for weekend/holiday payment." : "")
        );
    }

    public function logLocation(Request $request, Trip $trip)
    {
        abort_if($trip->driver_id !== auth()->id(), 403);
        abort_if($trip->status !== 'in_progress', 422, 'Trip must be active.');
        
        $validated = $request->validate([
            'location_name' => 'required|string|max:255',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'direction'     => 'required|in:morning,evening',
        ]);
        
        $sequence = $trip->locations()->where('direction', $validated['direction'])->count() + 1;
        TripLocation::create([...$validated, 'trip_id' => $trip->id, 'sequence' => $sequence, 'arrived_at' => now()]);
        
        return back()->with('success', "Stop #{$sequence} logged: {$validated['location_name']}");
    }

    public function markMaintenanceDone(Request $request, MonthlyMaintenanceLog $log)
    {
        abort_if($log->driver_id !== auth()->id(), 403);
        
        $log->update(['status' => 'done', 'done_at' => now(), 'notes' => $request->notes]);
        User::where('role', 'admin')->each(fn($a) => $a->notify(new MonthlyMaintenanceDoneNotification($log)));
        
        return back()->with('success', 'Monthly maintenance marked as done. Admin notified.');
    }

    public function reportMaintenanceNeed(Request $request)
    {
        $driver  = auth()->user();
        $vehicle = $driver->assignedVehicle;
        abort_unless($vehicle, 422, 'No vehicle assigned to you.');
        
        $validated = $request->validate([
            'need_type'          => 'required|string',
            'custom_description' => 'nullable|string|max:255',
            'urgency'            => 'required|in:low,medium,high,critical',
            'notes'              => 'nullable|string',
        ]);
        
        $need = VehicleMaintenanceNeed::create([
            ...$validated,
            'vehicle_id'     => $vehicle->id,
            'reported_by_id' => $driver->id,
        ]);
        
        User::where('role', 'admin')->each(fn($a) => $a->notify(new MaintenanceNeedAlert($need)));
        
        return back()->with('success', "Maintenance need reported: {$need->need_label}. Admin notified.");
    }
}