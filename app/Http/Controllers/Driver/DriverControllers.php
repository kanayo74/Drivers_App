<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\{Trip, TripLocation, FuelRequest, Vehicle, User};
use App\Services\FuelDepletionService;
use Illuminate\Http\Request;

class DriverDashboardController extends Controller
{
    public function index()
    {
        $driver = auth()->user();

        $pendingRequests = Trip::where('driver_id', $driver->id)
            ->where('status', 'approved')
            ->with(['passenger', 'vehicle', 'bookedBy'])
            ->orderBy('scheduled_at')
            ->get();

        $todayTrips = Trip::where('driver_id', $driver->id)
            ->today()
            ->with(['passenger', 'vehicle'])
            ->orderBy('scheduled_at')
            ->get();

        $activeTrip = Trip::where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->with(['passenger', 'vehicle', 'locations'])
            ->first();

        $vehicle = $driver->assignedVehicle;

        $stats = [
            'trips_this_month' => Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
            'avg_rating'       => round($driver->ratings()->avg('rating') ?? 0, 1),
            'outstanding_pay'  => $driver->outstanding_payment,
            'total_trips'      => $driver->total_trips,
        ];

        return view('driver.dashboard', compact(
            'pendingRequests', 'todayTrips', 'activeTrip', 'vehicle', 'stats'
        ));
    }
}

class DriverTripController extends Controller
{
    public function index(Request $request)
    {
        $driver = auth()->user();

        $trips = Trip::where('driver_id', $driver->id)
            ->with(['passenger', 'vehicle', 'rating'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->month,  fn($q) => $q->whereMonth('scheduled_at', $request->month))
            ->when($request->search, fn($q) => $q->where(function ($inner) use ($request) {
                $inner->where('trip_code', 'like', '%'.$request->search.'%')
                      ->orWhere('reason', 'like', '%'.$request->search.'%')
                      ->orWhereHas('passenger', fn($u) =>
                          $u->where('name', 'like', '%'.$request->search.'%')
                      );
            }))
            ->latest('scheduled_at')
            ->paginate(15);

        $rate = $driver->driverProfile?->per_trip_rate ?? 1200;

        $stats = [
            'total_completed' => Trip::where('driver_id', $driver->id)->where('status','completed')->count(),
            'this_month'      => Trip::where('driver_id', $driver->id)->where('status','completed')->whereMonth('completed_at', now()->month)->count(),
            'avg_rating'      => round($driver->ratings()->avg('rating') ?? 0, 1),
            'total_ratings'   => $driver->ratings()->count(),
            'total_earned'    => Trip::where('driver_id', $driver->id)->where('status','completed')->count() * $rate,
        ];

        return view('driver.trips.index', compact('trips', 'stats'));
    }

    public function start(Trip $trip)
    {
        abort_if($trip->driver_id !== auth()->id(), 403);
        abort_if($trip->status !== 'approved', 422, 'Trip is not ready to start.');

        $activeTrip = Trip::where('driver_id', auth()->id())->where('status','in_progress')->exists();
        if ($activeTrip) {
            return back()->with('error', 'You already have a trip in progress. Complete it first.');
        }

        $trip->update(['status' => 'in_progress', 'started_at' => now()]);
        optional(auth()->user()->driverProfile)->update(['status' => 'on_trip']);

        return back()->with('success', "Trip #{$trip->trip_code} started. Safe journey!");
    }

    public function complete(Trip $trip)
    {
        abort_if($trip->driver_id !== auth()->id(), 403);
        abort_if($trip->status !== 'in_progress', 422, 'Trip is not in progress.');

        $trip->update(['status' => 'completed', 'completed_at' => now()]);
        optional(auth()->user()->driverProfile)->update(['status' => 'available']);
        $trip->passenger->notify(new \App\Notifications\RateDriverNotification($trip));

        return back()->with('success', "Trip #{$trip->trip_code} completed!");
    }

    public function logLocation(Request $request, Trip $trip)
    {
        abort_if($trip->driver_id !== auth()->id(), 403);

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
}

class DriverFuelController extends Controller
{
    public function __construct(private FuelDepletionService $fuelService) {}

    public function request(Request $request)
    {
        $driver  = auth()->user();
        $vehicle = $driver->assignedVehicle;

        if (!$vehicle) {
            return back()->with('error', 'No vehicle assigned to you.');
        }

        $exists = FuelRequest::where('driver_id', $driver->id)
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['pending','acknowledged'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'You already have a pending fuel request.');
        }

        $dep = $this->fuelService->calculateDepletion($vehicle);

        FuelRequest::create([
            'vehicle_id'            => $vehicle->id,
            'driver_id'             => $driver->id,
            'current_level_litres'  => $vehicle->current_fuel_level,
            'current_level_percent' => $dep['percent'],
            'notes'                 => $request->notes,
        ]);

        User::where('role','admin')->each(fn($a) =>
            $a->notify(new \App\Notifications\FuelRequestedNotification($vehicle, $driver))
        );

        return back()->with('success', "Fuel request submitted. Current level: {$dep['percent']}%");
    }

    public function markFuelled(Request $request, Vehicle $vehicle)
    {
        abort_if($vehicle->assigned_driver_id !== auth()->id(), 403);
        $request->validate(['fill_type' => 'required|in:full,half', 'cost' => 'nullable|numeric|min:0']);

        $log = $this->fuelService->recordRefuel($vehicle, $request->fill_type, $request->cost);

        FuelRequest::where('vehicle_id', $vehicle->id)
            ->where('driver_id', auth()->id())
            ->whereIn('status', ['pending','acknowledged'])
            ->update(['status' => 'fuelled']);

        $eta = $log->estimated_empty_at?->format('D, d M H:i') ?? 'N/A';
        return back()->with('success',
            "Tank marked as {$request->fill_type}. Level: {$log->level_after}L. Est. empty: {$eta}"
        );
    }
}
