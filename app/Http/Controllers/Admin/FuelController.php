<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Vehicle, FuelRequest};
use App\Services\FuelDepletionService;
use App\Notifications\FuelAcknowledgedNotification;

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
            'status'             => 'acknowledged',
            'acknowledged_by_id' => auth()->id(),
            'acknowledged_at'    => now(),
        ]);

        $fuelRequest->driver->notify(new FuelAcknowledgedNotification($fuelRequest));

        return back()->with('success', 'Fuel request acknowledged. Driver notified.');
    }
}
