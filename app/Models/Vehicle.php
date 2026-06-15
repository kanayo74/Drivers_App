<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\CarbonInterface;

class Vehicle extends Model
{
	use HasFactory, SoftDeletes;
	
	protected $fillable = [
		'make', 'model', 'year', 'plate_number', 'type', 'color', 'vin',
		'engine_size', 'tank_capacity', 'fuel_consumption_per_hour',
		'current_fuel_level', 'fuel_status', 'assigned_driver_id',
		'status', 'last_service_date', 'next_service_date',
		'current_mileage', 'notes',
		'last_maintenance_at',
	];
	
	protected $casts = [
		'last_service_date' => 'date',
		'next_service_date' => 'date',
		'engine_size' => 'decimal:1',
		'tank_capacity' => 'decimal:2',
		'fuel_consumption_per_hour' => 'decimal:2',
		'current_fuel_level' => 'decimal:2',
	];
	
	// ── Relationships ──────────────────────────────────────────────
	
	public function assignedDriver()
	{
		return $this->belongsTo(User::class, 'assigned_driver_id');
	}
	
	public function trips()
	{
		return $this->hasMany(Trip::class);
	}
	
	public function fuelRequests()
	{
		return $this->hasMany(FuelRequest::class);
	}
	
	public function fuelLogs()
	{
		return $this->hasMany(FuelLog::class);
	}
	
	public function maintenanceRecords()
	{
		return $this->hasMany(MaintenanceRecord::class);
	}
	
	public function fuelSnapshots()
	{
		return $this->hasMany(FuelSnapshot::class);
	}
	
	// ── Computed attributes ────────────────────────────────────────
	
	/** Current fuel as percentage 0–100 */
	public function getFuelPercentAttribute():float
	{
		if ((float) $this->tank_capacity <= 0) return 0;
		return round(((float) $this->current_fuel_level / (float) $this->tank_capacity) * 100, 1);
	}
	
	/**
	 * Estimated hours of fuel remaining.
	 * Formula: current_litres / consumption_per_hour
	 */
	public function getEstimatedHoursRemainingAttribute():float
	{
		if ((float) $this->fuel_consumption_per_hour <= 0) return 0;
		return round((float) $this->current_fuel_level / (float) $this->fuel_consumption_per_hour, 1);
	}
	
	/**
	 * Estimated datetime when fuel will run out.
	 * Return type uses CarbonInterface to support both Carbon and CarbonImmutable.
	 */
	public function getEstimatedEmptyAtAttribute():?CarbonInterface
	{
		if ($this->estimated_hours_remaining <= 0) return NULL;
		return now()->addHours($this->estimated_hours_remaining);
	}
	
	/** Display name: "Toyota Hiace — LSR-874-KJ" */
	public function getDisplayNameAttribute():string
	{
		return "{$this->make} {$this->model} — {$this->plate_number}";
	}
	
	public function isCriticalFuel():bool
	{
		return $this->fuel_percent <= 25;
	}
	
	public function isServiceDue():bool
	{
		return $this->next_service_date && $this->next_service_date->isPast();
	}
	
	public function updateFuelStatus():void
	{
		$pct = $this->fuel_percent;
		$this->fuel_status = match (true) {
			$pct >= 90 => 'full',
			$pct >= 50 => 'half',
			$pct >= 25 => 'quarter',
			$pct > 5 => 'critical',
			default => 'empty',
		};
		$this->save();
	}
}
