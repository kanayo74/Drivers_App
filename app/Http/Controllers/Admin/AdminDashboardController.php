<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Trip, User, Vehicle, FuelRequest, MaintenanceRecord};
use App\Services\{PaymentService, AwardService};

class AdminDashboardController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private AwardService $awardService,
    ) {}

    public function index()
    {
        // ── All stats (no raw class calls in the view) ──────────────
        $stats = [
            'trips_today'        => Trip::today()->count(),
            'trips_in_progress'  => Trip::today()->where('status', 'in_progress')->count(),
            'pending_approvals'  => Trip::pending()->count(),
            'fuel_requests'      => FuelRequest::where('status', 'pending')->count(),
            'critical_vehicles'  => Vehicle::whereIn('fuel_status', ['critical', 'empty'])->count(),
            'total_outstanding'  => $this->paymentService->getOutstandingSummary()->sum('amount_due'),
            'drivers_count'      => User::where('role', 'driver')->count(),
            'active_vehicles'    => Vehicle::where('status', 'active')->count(),
            'overdue_services'   => MaintenanceRecord::where('status', 'overdue')->count(),
        ];

        // ── Data for dashboard panels ───────────────────────────────
        $pendingTrips  = Trip::pending()
            ->with(['bookedBy', 'passenger', 'driver', 'vehicle'])
            ->latest()
            ->take(10)
            ->get();

        $todayTrips = Trip::today()
            ->with(['passenger', 'driver', 'vehicle'])
            ->orderBy('scheduled_at')
            ->get();

        $fuelAlerts = Vehicle::whereIn('fuel_status', ['critical', 'empty'])
            ->with('assignedDriver')
            ->orderBy('current_fuel_level')
            ->get();

        $monthlyLeader = $this->awardService->getMonthlyLeaderboard()->take(5);

        // ── Data for the Book Trip modal ────────────────────────────
        $drivers = User::where('role', 'driver')
            ->where('is_active', true)
            ->with('assignedVehicle')
            ->get();

        $vehicles = Vehicle::where('status', 'active')->get();

        $staff = User::whereIn('role', ['staff', 'marketer', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'pendingTrips',
            'todayTrips',
            'fuelAlerts',
            'monthlyLeader',
            'drivers',
            'vehicles',
            'staff'
        ));
    }
}
