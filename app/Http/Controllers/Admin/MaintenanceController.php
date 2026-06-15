<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use DB;
use App\Models\{MaintenanceRecord, Vehicle, User};
use App\Notifications\MaintenanceNeeded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Throwable;

class MaintenanceController extends Controller
{
	public function index()
	{
		// Auto-mark overdue
		MaintenanceRecord::where('status', 'scheduled')
			->whereDate('next_service_date', '<', today())
			->update(['status' => 'overdue']);
		
		$records = MaintenanceRecord::with(['vehicle', 'loggedBy'])
			->orderByDesc('created_at')
			->paginate(20);
		
		$vehicles = Vehicle::with(['assignedDriver', 'maintenanceRecords'])->where('status', 'active')->get();
		
		return view('admin.maintenance.index', compact('records', 'vehicles'));
	}
	
	/**
	 * Mark monthly maintenance as done for a vehicle
	 */
	public function markDone(Vehicle $vehicle)
	{
		// Create a maintenance record for monthly service
		$vehicle->maintenanceRecords()->where('status', '!=', 'completed')->update(['status' => 'completed']);
		return back()->with('success', 'Monthly maintenance marked as completed for ' . $vehicle->plate_number);
	}
	
	public function reportIssue(Request $request, Vehicle $vehicle)
	{
		try {
			return DB::transaction(function () use ($vehicle, $request) {
				$data = $request->validate([
					'issue' => 'required|string|max:1000',
					'type' => 'nullable|string',
				]);
				
				// Also create a maintenance record with status 'reported'
				MaintenanceRecord::create([
					'vehicle_id' => $vehicle->id,
					'service_type' => $data['type'] ?? 'Issue Report',
					'service_date' => now(),
					'status' => 'pending',
					'logged_by_id' => auth()->id(),
					'notes' => "Issue reported: " . $data['issue'],
				]);
				
				// notify admins
				$admins = User::where('role', 'admin')->get();
				
				if ($admins->count())
					Notification::send($admins, new MaintenanceNeeded($vehicle, $data['issue'], $data['type'] ?? NULL));
				
				return back()->with('success', 'Issue reported to admin.');
			});
		} catch (Throwable $exception) {
			return back()->with('error', 'Recod creation failed!');
			return back()->with('error', $exception->getMessage());
		}
	}
	
	public function store(Request $request)
	{
		try {
			return DB::transaction(function () use ($request) {
				$validated = $request->validate([
					'vehicle_id' => 'required|exists:vehicles,id',
					'service_type' => 'required|string',
					'service_date' => 'required|date',
					'next_service_date' => 'nullable|date|after:service_date',
					'mileage_at_service' => 'nullable|integer',
					'provider' => 'nullable|string',
					'cost' => 'nullable|numeric|min:0',
					'notes' => 'nullable|string',
				]);
				
				if (!Vehicle::find($validated['vehicle_id'])->maintenanceRecords()->where('status', 'pending')->exists()) {
					// Create the maintenance record
					MaintenanceRecord::create([
						...$validated,
						'status' => 'completed',
						'logged_by_id' => auth()->id(),
					]);
					
					// Update vehicle with service dates
					$vehicle = Vehicle::find($validated['vehicle_id']);
					$vehicle?->update([
						'last_service_date' => $validated['service_date'],
						'next_service_date' => $validated['next_service_date'] ?? NULL,
					]);
					return back()->with('success', 'Maintenance record saved.');
				}
				return back()->with('error', 'Vehicle has pending maintenance records.');
				
			});
		} catch (Throwable $exception) {
			return back()->with('error', 'Recod creation failed!');
			return back()->with('error', $exception->getMessage());
		}
	}
}
