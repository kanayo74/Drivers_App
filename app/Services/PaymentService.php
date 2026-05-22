<?php

namespace App\Services;

use App\Models\User;
use App\Models\Trip;
use App\Models\DriverPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Payments can ONLY be processed on weekends (Saturday or Sunday).
     */
    public function isPaymentWindow(): bool
    {
        return now()->isWeekend();
    }

    /**
     * Get outstanding (unpaid) payment summary for all drivers.
     */
    public function getOutstandingSummary(): \Illuminate\Support\Collection
    {
        return User::where('role', 'driver')
            ->with('driverProfile')
            ->get()
            ->map(function (User $driver) {
                $unpaidTrips = Trip::where('driver_id', $driver->id)
                    ->where('status', 'completed')
                    ->where('payment_processed', false)
                    ->count();

                $rate = $driver->driverProfile?->per_trip_rate ?? 1200;

                return [
                    'driver'         => $driver,
                    'unpaid_trips'   => $unpaidTrips,
                    'rate'           => $rate,
                    'amount_due'     => $unpaidTrips * $rate,
                ];
            })->filter(fn($d) => $d['unpaid_trips'] > 0);
    }

    /**
     * Process payment for a single driver.
     * Only works on weekends.
     */
    public function processPayment(User $driver, User $admin, ?string $reference = null): DriverPayment
    {
        if (!$this->isPaymentWindow()) {
            throw new \Exception('Driver payments can only be processed on weekends (Saturday or Sunday).');
        }

        return DB::transaction(function () use ($driver, $admin, $reference) {
            $unpaidTrips = Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('payment_processed', false)
                ->get();

            if ($unpaidTrips->isEmpty()) {
                throw new \Exception('No unpaid trips found for this driver.');
            }

            $rate  = $driver->driverProfile?->per_trip_rate ?? 1200;
            $count = $unpaidTrips->count();
            $total = $count * $rate;

            // Mark all trips as payment processed
            Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('payment_processed', false)
                ->update(['payment_processed' => true]);

            // Record payment
            return DriverPayment::create([
                'driver_id'         => $driver->id,
                'processed_by_id'   => $admin->id,
                'trips_count'       => $count,
                'amount_per_trip'   => $rate,
                'total_amount'      => $total,
                'period_start'      => $unpaidTrips->min('completed_at'),
                'period_end'        => $unpaidTrips->max('completed_at'),
                'status'            => 'paid',
                'paid_at'           => now(),
                'payment_reference' => $reference ?? 'PAY-' . strtoupper(\Str::random(8)),
            ]);
        });
    }

    /**
     * Process payments for ALL drivers at once.
     */
    public function processAllPayments(User $admin): array
    {
        if (!$this->isPaymentWindow()) {
            throw new \Exception('Payments can only be processed on weekends.');
        }

        $results = [];
        $drivers = User::where('role', 'driver')->get();

        foreach ($drivers as $driver) {
            $unpaid = Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->where('payment_processed', false)
                ->count();

            if ($unpaid > 0) {
                $results[] = $this->processPayment($driver, $admin);
            }
        }

        return $results;
    }

    /**
     * Payment history for a driver.
     */
    public function getPaymentHistory(User $driver): \Illuminate\Support\Collection
    {
        return DriverPayment::where('driver_id', $driver->id)
            ->with('processedBy')
            ->orderByDesc('paid_at')
            ->get();
    }
}
