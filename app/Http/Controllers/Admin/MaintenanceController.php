<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{MaintenanceRecord, Vehicle};
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        // Auto-mark overdue
        MaintenanceRecord::where('status', 'scheduled')
            ->whereDate('next_service_date', '<', today())
            ->update(['status' => 'overdue']);

        $records  = MaintenanceRecord::with(['vehicle', 'loggedBy'])
            ->orderByDesc('service_date')
            ->paginate(20);

        $vehicles = Vehicle::where('status', 'active')->get();

        return view('admin.maintenance.index', compact('records', 'vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id'        => 'required|exists:vehicles,id',
            'service_type'      => 'required|string',
            'service_date'      => 'required|date',
            'next_service_date' => 'nullable|date|after:service_date',
            'mileage_at_service'=> 'nullable|integer',
            'provider'          => 'nullable|string',
            'cost'              => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        MaintenanceRecord::create([
            ...$validated,
            'status'       => 'completed',
            'logged_by_id' => auth()->id(),
        ]);

        Vehicle::where('id', $validated['vehicle_id'])->update([
            'last_service_date' => $validated['service_date'],
            'next_service_date' => $validated['next_service_date'] ?? null,
        ]);

        return back()->with('success', 'Maintenance record saved.');
    }
}
