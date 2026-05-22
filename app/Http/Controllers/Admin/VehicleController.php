<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Vehicle, User};
use App\Services\FuelDepletionService;
use Illuminate\Http\Request;

// ── VehicleController ─────────────────────────────────────────────
class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('assignedDriver')->withCount('trips')->orderBy('type')->get();
        $drivers  = User::where('role', 'driver')->where('is_active', true)
            ->doesntHave('assignedVehicle')->get();
        return view('admin.vehicles.index', compact('vehicles', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'make'                      => 'required|string',
            'model'                     => 'required|string',
            'year'                      => 'required|digits:4',
            'plate_number'              => 'required|string|unique:vehicles',
            'type'                      => 'required|in:staff_bus,marketing_car,executive_car,other',
            'engine_size'               => 'required|numeric|min:0.5',
            'tank_capacity'             => 'required|numeric|min:10',
            'fuel_consumption_per_hour' => 'required|numeric|min:0.1',
            'assigned_driver_id'        => 'nullable|exists:users,id',
            'color'                     => 'nullable|string',
        ]);
        Vehicle::create($validated);
        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle added to fleet.');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['assignedDriver', 'fuelLogs', 'maintenanceRecords', 'trips']);
        $fuelHistory = $vehicle->fuelSnapshots()->latest('recorded_at')->take(24)->get();
        return view('admin.vehicles.show', compact('vehicle', 'fuelHistory'));
    }

    public function assignDriver(Request $request, Vehicle $vehicle)
    {
        $request->validate(['driver_id' => 'required|exists:users,id']);
        $vehicle->update(['assigned_driver_id' => $request->driver_id]);
        return back()->with('success', 'Driver assigned to vehicle.');
    }
}
