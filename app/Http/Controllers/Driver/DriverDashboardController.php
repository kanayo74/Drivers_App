<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Trip;

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
            'avg_rating'      => round($driver->ratings()->avg('rating') ?? 0, 1),
            'outstanding_pay' => $driver->outstanding_payment,
            'total_trips'     => $driver->total_trips,
        ];

        return view('driver.dashboard', compact(
            'pendingRequests', 'todayTrips', 'activeTrip', 'vehicle', 'stats'
        ));
    }
}
