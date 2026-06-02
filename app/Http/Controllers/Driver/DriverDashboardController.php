<?php
namespace App\Http\Controllers\Driver;
use App\Http\Controllers\Controller;
use App\Models\{Trip, MonthlyMaintenanceLog};

class DriverDashboardController extends Controller
{
    public function index()
    {
        $driver = auth()->user();

        $pendingRequests = Trip::where('driver_id', $driver->id)
            ->where('status', 'approved')
            ->with(['passenger', 'vehicle', 'bookedBy'])
            ->orderBy('scheduled_at')->get();

        $todayTrips = Trip::where('driver_id', $driver->id)
            ->today()->with(['passenger', 'vehicle'])
            ->orderBy('scheduled_at')->get();

        $activeTrip = Trip::where('driver_id', $driver->id)
            ->where('status', 'in_progress')
            ->with(['passenger', 'vehicle', 'locations'])->first();

        $vehicle = $driver->assignedVehicle;

        $monthlyLog = $vehicle
            ? MonthlyMaintenanceLog::where('vehicle_id', $vehicle->id)
                ->where('month', now()->month)->where('year', now()->year)->first()
            : null;

        $upcomingTraining = $driver->trainingAssignments()
            ->whereIn('status', ['upcoming', 'in_progress'])
            ->orderBy('training_date')->first();

        $recentRatings = $driver->ratings()->with('ratedBy', 'trip')->latest()->take(3)->get();

        $rate = $driver->driverProfile?->per_trip_rate ?? 1200;

        $stats = [
            'trips_this_month' => Trip::where('driver_id', $driver->id)->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year)->count(),
            'avg_rating'       => round($driver->ratings()->avg('rating') ?? 0, 1),
            'outstanding_pay'  => $driver->outstanding_payment,
            'total_trips'      => $driver->total_trips,
            'weekend_trips'    => Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')->where('qualifies_for_payment', true)->count(),
        ];

        return view('driver.dashboard', compact(
            'pendingRequests', 'todayTrips', 'activeTrip', 'vehicle',
            'monthlyLog', 'upcomingTraining', 'recentRatings', 'stats'
        ));
    }
}
