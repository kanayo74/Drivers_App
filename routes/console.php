<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\{FuelDepletionService, AwardService};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Hourly fuel depletion (6am – 10pm only) ──────────────────────────
Schedule::call(function () {
    app(FuelDepletionService::class)->runHourlyDepletion();
})->hourly()->between('6:00', '22:00')->name('fuel-depletion')->withoutOverlapping();

// ── Driver of the Month — 1st of every month ─────────────────────────
Schedule::call(function () {
    $last = now()->subMonth();
    app(AwardService::class)->calculateMonthlyAward($last->year, $last->month);
})->monthly()->name('monthly-award')->withoutOverlapping();

// ── Driver of the Year — Jan 1st each year ────────────────────────────
Schedule::call(function () {
    app(AwardService::class)->calculateYearlyAward(now()->subYear()->year);
})->yearlyOn(1, 1, '01:00')->name('yearly-award');

// ── Mark overdue maintenance daily at 7am ────────────────────────────
Schedule::call(function () {
    \App\Models\MaintenanceRecord::where('status', 'scheduled')
        ->whereDate('next_service_date', '<', today())
        ->update(['status' => 'overdue']);
})->dailyAt('07:00')->name('maintenance-check');

// ── Clean old fuel snapshots weekly ──────────────────────────────────
Schedule::call(function () {
    \App\Models\FuelSnapshot::where('recorded_at', '<', now()->subDays(30))->delete();
})->weekly()->name('cleanup-snapshots');
