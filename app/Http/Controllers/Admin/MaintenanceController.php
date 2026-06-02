<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{MaintenanceRecord, Vehicle, User};
use App\Notifications\MaintenanceNeeded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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

    /**
     * Mark monthly maintenance as done for a vehicle
     */
    public function markDone(Vehicle $vehicle)
    {
        // Create a maintenance record for monthly service
        $record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'service_type' => 'Monthly maintenance',
            'service_date' => now(),
            'next_service_date' => now()->addMonth(),
            'status' => 'completed',
            'logged_by_id' => auth()->id(),
            'notes' => 'Regular monthly maintenance completed',
        ]);

        // Update vehicle's last maintenance date
        $vehicle->update([
            'last_maintenance_at' => now(),
            'last_service_date' => now(),
            'next_service_date' => now()->addMonth(),
        ]);

        return back()->with('success', 'Monthly maintenance marked as completed for ' . $vehicle->plate_number);
    }

    public function reportIssue(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'issue' => 'required|string|max:1000',
            'type' => 'nullable|string',
        ]);

        // notify admins
        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
        
        if ($admins->count() > 0) {
            Notification::send($admins, new MaintenanceNeeded($vehicle, $data['issue'], $data['type'] ?? null));
        }

        // Also create a maintenance record with status 'reported'
        MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'service_type' => $data['type'] ?? 'Issue Report',
            'service_date' => now(),
            'status' => 'pending',
            'logged_by_id' => auth()->id(),
            'notes' => "Issue reported: " . $data['issue'],
        ]);

        return back()->with('success', 'Issue reported to admin.');
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

        // Create the maintenance record
        $record = MaintenanceRecord::create([
            ...$validated,
            'status'       => 'completed',
            'logged_by_id' => auth()->id(),
        ]);

        // Update vehicle with service dates
        $vehicle = Vehicle::find($validated['vehicle_id']);
        if ($vehicle) {
            $vehicle->update([
                'last_maintenance_at' => $validated['service_date'],
                'last_service_date' => $validated['service_date'],
                'next_service_date' => $validated['next_service_date'] ?? null,
            ]);
        }

        return back()->with('success', 'Maintenance record saved.');
    }
}