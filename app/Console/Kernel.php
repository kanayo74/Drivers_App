<?php

namespace App\Console;

use App\Services\{FuelDepletionService, AwardService};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ── Fuel depletion: runs every hour during working hours ──────────────
        $schedule->call(function () {
            app(FuelDepletionService::class)->runHourlyDepletion();
        })->hourly()
          ->between('6:00', '22:00')
          ->name('fuel-depletion')
          ->withoutOverlapping();

        // ── Driver of the Month: runs on 1st of each month at midnight ────────
        $schedule->call(function () {
            $lastMonth = now()->subMonth();
            app(AwardService::class)->calculateMonthlyAward(
                $lastMonth->year,
                $lastMonth->month
            );
        })->monthly()
          ->name('monthly-award')
          ->withoutOverlapping();

        // ── Driver of the Year: runs on Jan 1st for the previous year ─────────
        $schedule->call(function () {
            app(AwardService::class)->calculateYearlyAward(now()->subYear()->year);
        })->yearlyOn(1, 1, '01:00')
          ->name('yearly-award');

        // ── Mark overdue maintenance records daily ────────────────────────────
        $schedule->call(function () {
            \App\Models\MaintenanceRecord::where('status', 'scheduled')
                ->whereDate('next_service_date', '<', today())
                ->update(['status' => 'overdue']);

            // Notify admins of any newly overdue vehicles
            $overdue = \App\Models\Vehicle::whereHas('maintenanceRecords', fn($q) =>
                $q->where('status', 'overdue')
                  ->whereDate('next_service_date', today())
            )->get();

            if ($overdue->isNotEmpty()) {
                \App\Models\User::where('role', 'admin')->each(function ($admin) use ($overdue) {
                    foreach ($overdue as $vehicle) {
                        $admin->notify(new \App\Notifications\MaintenanceOverdueAlert($vehicle));
                    }
                });
            }
        })->dailyAt('07:00')
          ->name('maintenance-check');

        // ── Clean up old fuel snapshots (keep last 30 days) ──────────────────
        $schedule->call(function () {
            \App\Models\FuelSnapshot::where('recorded_at', '<', now()->subDays(30))->delete();
        })->weekly()
          ->name('cleanup-snapshots');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
