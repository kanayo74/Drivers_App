<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\FuelLog;
use App\Models\FuelSnapshot;
use Carbon\Carbon;

class FuelDepletionService
{
    /**
     * Calculate and update the estimated empty time for a vehicle.
     *
     * Formula:
     *   hours_remaining = current_fuel_litres / consumption_litres_per_hour
     *   estimated_empty_at = now() + hours_remaining
     */
    public function calculateDepletion(Vehicle $vehicle): array
    {
        $litres       = $vehicle->current_fuel_level;
        $consumption  = $vehicle->fuel_consumption_per_hour; // L/hr
        $tank         = $vehicle->tank_capacity;

        if ($consumption <= 0 || $litres <= 0) {
            return [
                'hours_remaining'    => 0,
                'estimated_empty_at' => null,
                'percent'            => 0,
            ];
        }

        $hoursRemaining  = round($litres / $consumption, 2);
        $estimatedEmptyAt = Carbon::now()->addHours($hoursRemaining);
        $percent          = round(($litres / $tank) * 100, 1);

        return [
            'hours_remaining'    => $hoursRemaining,
            'estimated_empty_at' => $estimatedEmptyAt,
            'percent'            => $percent,
        ];
    }

    /**
     * When a driver marks fuel as Full or Half, update vehicle level.
     */
    public function recordRefuel(Vehicle $vehicle, string $fillType, ?float $cost = null): FuelLog
    {
        $levelBefore = $vehicle->current_fuel_level;

        // Full = 100% of tank; Half = 50% of tank
        $levelAfter = match($fillType) {
            'full'  => $vehicle->tank_capacity,
            'half'  => $vehicle->tank_capacity * 0.5,
            default => $vehicle->tank_capacity,
        };

        $litresAdded  = max(0, $levelAfter - $levelBefore);
        $depletion    = $this->calculateDepletion((object) [
            'current_fuel_level'        => $levelAfter,
            'fuel_consumption_per_hour' => $vehicle->fuel_consumption_per_hour,
            'tank_capacity'             => $vehicle->tank_capacity,
        ]);

        // Update vehicle
        $vehicle->update([
            'current_fuel_level' => $levelAfter,
            'fuel_status'        => $fillType === 'full' ? 'full' : 'half',
        ]);

        // Log it
        $log = FuelLog::create([
            'vehicle_id'                => $vehicle->id,
            'driver_id'                 => $vehicle->assigned_driver_id,
            'fill_type'                 => $fillType,
            'litres_added'              => $litresAdded,
            'level_before'              => $levelBefore,
            'level_after'               => $levelAfter,
            'estimated_hours_remaining' => $depletion['hours_remaining'],
            'estimated_empty_at'        => $depletion['estimated_empty_at'],
            'cost'                      => $cost,
        ]);

        // Save snapshot for dashboard charting
        FuelSnapshot::create([
            'vehicle_id'          => $vehicle->id,
            'level_litres'        => $levelAfter,
            'level_percent'       => $depletion['percent'],
            'estimated_empty_at'  => $depletion['estimated_empty_at'],
            'recorded_at'         => now(),
        ]);

        return $log;
    }

    /**
     * Run hourly via scheduler: decrement fuel for active vehicles and
     * take a snapshot. Also flags critical vehicles.
     */
    public function runHourlyDepletion(): void
    {
        $vehicles = Vehicle::where('status', 'active')
            ->where('current_fuel_level', '>', 0)
            ->get();

        foreach ($vehicles as $vehicle) {
            // Deduct one hour of consumption (only during working hours 6am–10pm)
            $hour = now()->hour;
            if ($hour >= 6 && $hour <= 22) {
                $newLevel = max(0, $vehicle->current_fuel_level - $vehicle->fuel_consumption_per_hour);
                $vehicle->current_fuel_level = $newLevel;
                $vehicle->updateFuelStatus();
            }

            $depletion = $this->calculateDepletion($vehicle);

            // Snapshot
            FuelSnapshot::create([
                'vehicle_id'         => $vehicle->id,
                'level_litres'       => $vehicle->current_fuel_level,
                'level_percent'      => $depletion['percent'],
                'estimated_empty_at' => $depletion['estimated_empty_at'],
                'recorded_at'        => now(),
            ]);

            // Dispatch alert if critical
            if ($vehicle->isCriticalFuel()) {
                // Notify admin — see Notifications/FuelCriticalAlert.php
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\FuelCriticalAlert($vehicle));
                }
            }
        }
    }
}
