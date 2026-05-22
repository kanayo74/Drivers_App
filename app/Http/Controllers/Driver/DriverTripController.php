<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\{Trip, TripLocation};
use App\Notifications\RateDriverNotification;
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
                  ->orWhereHas('passenger', fn($u) =>
                      $u->where('name', 'like', "%{$request->search}%")
                  );
            }))
            ->latest('scheduled_at')
            ->paginate(15);

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

        if (Trip::where('driver_id', auth()->id())->where('status','in_progress')->exists()) {
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
        $trip->passenger->notify(new RateDriverNotification($trip));

        return back()->with('success', "Trip #{$trip->trip_code} completed! Passenger notified to rate you.");
    }

    public function logLocation(Request $request, Trip $trip)
    {
        abort_if($trip->driver_id !== auth()->id(), 403);
        abort_if($trip->status !== 'in_progress', 422, 'Trip must be active to log stops.');

        $validated = $request->validate([
            'location_name' => 'required|string|max:255',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'direction'     => 'required|in:morning,evening',
        ]);

        $sequence = $trip->locations()->where('direction', $validated['direction'])->count() + 1;

        TripLocation::create([
            ...$validated,
            'trip_id'    => $trip->id,
            'sequence'   => $sequence,
            'arrived_at' => now(),
        ]);

        return back()->with('success', "Stop #{$sequence} logged: {$validated['location_name']}");
    }
}
