<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\{FuelRequest, Vehicle, User};
use App\Notifications\FuelRequestedNotification;
use App\Services\FuelDepletionService;
use Illuminate\Http\Request;

class DriverFuelController extends Controller
{
    public function __construct(private FuelDepletionService $fuelService) {}

    public function request(Request $request)
    {
        $driver  = auth()->user();
        $vehicle = $driver->assignedVehicle;

        if (!$vehicle) {
            return back()->with('error', 'No vehicle assigned to you. Contact admin.');
        }

        $exists = FuelRequest::where('driver_id', $driver->id)
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', ['pending','acknowledged'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'You already have a pending fuel request. Wait for admin acknowledgement.');
        }

        $dep = $this->fuelService->calculateDepletion($vehicle);

        FuelRequest::create([
            'vehicle_id'            => $vehicle->id,
            'driver_id'             => $driver->id,
            'current_level_litres'  => $vehicle->current_fuel_level,
            'current_level_percent' => $dep['percent'],
            'notes'                 => $request->notes,
        ]);

        User::where('role', 'admin')->each(fn($admin) =>
            $admin->notify(new FuelRequestedNotification($vehicle, $driver))
        );

        return back()->with('success', "Fuel request submitted. Current level: {$dep['percent']}%");
    }

    public function markFuelled(Request $request, Vehicle $vehicle)
    {
        abort_if($vehicle->assigned_driver_id !== auth()->id(), 403, 'This vehicle is not assigned to you.');

        $request->validate([
            'fill_type' => 'required|in:full,half',
            'cost'      => 'nullable|numeric|min:0',
        ]);

        $log = $this->fuelService->recordRefuel($vehicle, $request->fill_type, $request->cost);

        FuelRequest::where('vehicle_id', $vehicle->id)
            ->where('driver_id', auth()->id())
            ->whereIn('status', ['pending','acknowledged'])
            ->update(['status' => 'fuelled']);

        $eta = $log->estimated_empty_at?->format('D, d M H:i') ?? 'N/A';
        $hrs = round($log->estimated_hours_remaining, 1);

        return back()->with('success',
            "Tank marked as {$request->fill_type}. Level: {$log->level_after}L. Est. empty: {$eta} (~{$hrs} hrs)"
        );
    }
}
