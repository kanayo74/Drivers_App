<?php

namespace App\Services;

use App\Models\{Vehicle, FuelLog, FuelSnapshot, User};
use Carbon\CarbonInterface;

class FuelDepletionService
{
    /**
     * Calculate depletion for a vehicle or any object with the needed properties.
     */
    public function calculateDepletion(Vehicle $vehicle): array
    {
        $litres      = (float) $vehicle->current_fuel_level;
        $consumption = (float) $vehicle->fuel_consumption_per_hour;
        $tank        = (float) $vehicle->tank_capacity;

        if ($consumption <= 0 || $litres <= 0) {
            return [
                'hours_remaining'    => 0,
                'estimated_empty_at' => null,
                'percent'            => 0,
            ];
        }

        $hoursRemaining   = round($litres / $consumption, 2);
        $estimatedEmptyAt = now()->addHours($hoursRemaining);
        $percent          = $tank > 0 ? round(($litres / $tank) * 100, 1) : 0;

        return [
            'hours_remaining'    => $hoursRemaining,
            'estimated_empty_at' => $estimatedEmptyAt,
            'percent'            => $percent,
        ];
    }

    /**
     * Record a refuel event — driver marked Full or Half.
     */
    public function recordRefuel(Vehicle $vehicle, string $fillType, ?float $cost = null): FuelLog
    {
        $levelBefore = (float) $vehicle->current_fuel_level;
        $tank        = (float) $vehicle->tank_capacity;

        $levelAfter = match($fillType) {
            'full'  => $tank,
            'half'  => $tank * 0.5,
            default => $tank,
        };

        $litresAdded = max(0, $levelAfter - $levelBefore);

        // Update vehicle
        $vehicle->current_fuel_level = $levelAfter;
        $vehicle->fuel_status        = $fillType === 'full' ? 'full' : 'half';
        $vehicle->save();

        $depletion = $this->calculateDepletion($vehicle);

        // Log the refuel
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

        // Save snapshot
        FuelSnapshot::create([
            'vehicle_id'         => $vehicle->id,
            'level_litres'       => $levelAfter,
            'level_percent'      => $depletion['percent'],
            'estimated_empty_at' => $depletion['estimated_empty_at'],
            'recorded_at'        => now(),
        ]);

        return $log;
    }

    /**
     * Hourly scheduler job — decrement fuel for all active vehicles.
     */
    public function runHourlyDepletion(): void
    {
        $vehicles = Vehicle::where('status', 'active')
            ->where('current_fuel_level', '>', 0)
            ->get();

        $hour = now()->hour;

        foreach ($vehicles as $vehicle) {
            // Only deplete during working hours 6am–10pm
            if ($hour >= 6 && $hour <= 22) {
                $newLevel = max(0, (float) $vehicle->current_fuel_level
                    - (float) $vehicle->fuel_consumption_per_hour);
                $vehicle->current_fuel_level = $newLevel;
                $vehicle->updateFuelStatus();
            }

            $depletion = $this->calculateDepletion($vehicle);

            FuelSnapshot::create([
                'vehicle_id'         => $vehicle->id,
                'level_litres'       => $vehicle->current_fuel_level,
                'level_percent'      => $depletion['percent'],
                'estimated_empty_at' => $depletion['estimated_empty_at'],
                'recorded_at'        => now(),
            ]);

            // Alert admins if critical
            if ($vehicle->isCriticalFuel()) {
                User::where('role', 'admin')->each(function ($admin) use ($vehicle) {
                    $admin->notify(new \App\Notifications\FuelCriticalAlert($vehicle));
                });
            }
        }
    }
}
