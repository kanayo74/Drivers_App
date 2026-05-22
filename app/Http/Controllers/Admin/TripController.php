<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Trip, User, Vehicle};
use App\Notifications\{TripApprovedNotification, TripRejectedNotification, TripRequestNotification};
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $trips = Trip::with(['bookedBy', 'passenger', 'driver', 'vehicle'])
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->when($request->search,  fn($q) => $q->whereHas('passenger', fn($u) =>
                $u->where('name', 'like', "%{$request->search}%")
            ))
            ->when($request->date, fn($q) => $q->whereDate('scheduled_at', $request->date))
            ->latest()
            ->paginate(20);

        $drivers  = User::where('role', 'driver')->where('is_active', true)->with('assignedVehicle')->get();
        $vehicles = Vehicle::where('status', 'active')->get();
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

        $trip->driver->notify(new TripRequestNotification($trip));

        return redirect()->route('admin.trips.index')
            ->with('success', "Trip #{$trip->trip_code} booked and driver notified.");
    }

    public function approve(Trip $trip)
    {
        abort_if(!$trip->isPending(), 403, 'Trip is not pending approval.');

        $trip->update([
            'status'         => 'approved',
            'approved_by_id' => auth()->id(),
            'approved_at'    => now(),
        ]);

        optional($trip->driver->driverProfile)->update(['status' => 'on_trip']);
        $trip->driver->notify(new TripApprovedNotification($trip));

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

        $trip->driver->notify(new TripRejectedNotification($trip));

        return back()->with('success', "Trip #{$trip->trip_code} rejected.");
    }

    public function show(Trip $trip)
    {
        $trip->load(['bookedBy', 'passenger', 'driver', 'vehicle', 'locations', 'rating', 'approvedBy']);
        return view('admin.trips.show', compact('trip'));
    }
}
