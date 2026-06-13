<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
namespace App\Http\Controllers\Admin;

use App\Models\Driver;
use Illuminate\Support\Facades\Storage;
use App\Notifications\LicenseExpiryNotification;
use Illuminate\Support\Facades\Notification;

class DriverController extends Controller
{
	public function index()
	{
		$drivers = User::where('role', 'driver')
			->with(['driverProfile', 'assignedVehicle', 'ratings'])
			->withCount(['drivenTrips as completed_trips' => fn ($q) => $q->where('status', 'completed')])
			->get()
			->map(function ($driver) {
				$driver->avg_rating = round($driver->ratings->avg('rating') ?? 0, 1);
				$driver->outstanding = $driver->outstanding_payment;
				return $driver;
			});
		
		return view('admin.drivers.index', compact('drivers'));
	}
	
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|max:255',
			'email' => 'required|email|unique:users',
			'phone' => 'required|string',
			'employee_id' => 'nullable|string|unique:users',
			'license_number' => 'required|string|unique:driver_profiles',
			'license_expiry' => 'required|date',
			'license_class' => 'required|in:A,B,C,D,E',
			'years_experience' => 'required|integer|min:0',
			'per_trip_rate' => 'required|numeric|min:0',
			'license_number' => 'nullable|string|max:255',
			'license_start_date' => 'nullable|date',
			'license_end_date' => 'nullable|date',
			'license_file' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
			'weekend_only' => 'nullable|boolean',
		]);
		
		if ($request->hasFile('license_file')) {
			if ($driver->license_file) {
				Storage::delete($driver->license_file);
			}
			$path = $request->file('license_file')->store('licenses');
			$data['license_file'] = $path;
		}
		
		$data['weekend_only'] = $request->has('weekend_only') ? (bool) $request->input('weekend_only') : $driver->weekend_only;
		
		$driver->update($data);
		return back()->with('success', 'Driver license updated.');
	}
	
	public function remove(Request $request, Driver $driver)
	{
		// soft-flag the driver as left; admin can still view history
		$driver->update(['left_company' => true]);
		return back()->with('success', 'Driver marked as left the company.');
	}
	
	public function training(Driver $driver)
	{
		return view('admin.drivers.training', compact('driver'));
		
		$user = User::create([
			'name' => $validated['name'],
			'email' => $validated['email'],
			'phone' => $validated['phone'],
			'employee_id' => $validated['employee_id'] ?? NULL,
			'role' => 'driver',
			'password' => bcrypt('nsia@driver123'),
		]);
		
		$user->driverProfile()->create([
			'license_number' => $validated['license_number'],
			'license_expiry' => $validated['license_expiry'],
			'license_class' => $validated['license_class'],
			'years_experience' => $validated['years_experience'],
			'per_trip_rate' => $validated['per_trip_rate'],
		]);
		
		return redirect()->route('admin.drivers.index')
			->with('success', "Driver {$user->name} created. Default password: nsia@driver123");
	}
	
	public function show(User $driver)
	{
		abort_if($driver->role !== 'driver', 404);
		$driver->load(['driverProfile', 'assignedVehicle', 'trainingAssignments', 'awards', 'payments']);
		$recentTrips = $driver->drivenTrips()->with('passenger', 'vehicle')->latest()->take(10)->get();
		$recentRatings = $driver->ratings()->with('ratedBy', 'trip')->latest()->take(10)->get();
		return view('admin.drivers.show', compact('driver', 'recentTrips', 'recentRatings'));
	}
	
	public function updateStatus(Request $request, User $driver)
	{
		$request->validate(['status' => 'required|in:available,on_trip,off_duty,training,suspended']);
		$driver->driverProfile()->update(['status' => $request->status]);
		return back()->with('success', 'Driver status updated.');
	}
}
