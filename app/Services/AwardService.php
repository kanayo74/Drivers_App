<?php

namespace App\Services;

use App\Models\User;
use App\Models\DriverAward;
use App\Models\DriverRating;
use App\Models\Trip;
use Carbon\Carbon;

class AwardService
{
    /**
     * Calculate and store Driver of the Month.
     * Called by scheduler on the 1st of every month.
     */
    public function calculateMonthlyAward(int $year, int $month): ?DriverAward
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $drivers = User::where('role', 'driver')->get();

        $scores = $drivers->map(function (User $driver) use ($start, $end) {
            $trips = Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $avgRating = DriverRating::where('driver_id', $driver->id)
                ->whereBetween('created_at', [$start, $end])
                ->avg('rating') ?? 0;

            // Score = avg_rating * 20 + trips (weighted formula)
            $score = ($avgRating * 20) + $trips;

            return [
                'driver'      => $driver,
                'trips'       => $trips,
                'avg_rating'  => round($avgRating, 2),
                'score'       => round($score, 2),
            ];
        })->filter(fn($d) => $d['trips'] > 0)
          ->sortByDesc('score');

        if ($scores->isEmpty()) return null;

        $winner = $scores->first();

        // Avoid duplicate
        DriverAward::updateOrCreate(
            ['award_type' => 'driver_of_month', 'year' => $year, 'month' => $month],
            [
                'driver_id'      => $winner['driver']->id,
                'average_rating' => $winner['avg_rating'],
                'total_trips'    => $winner['trips'],
                'score'          => $winner['score'],
            ]
        );

        return DriverAward::where('award_type', 'driver_of_month')
            ->where('year', $year)->where('month', $month)->first();
    }

    /**
     * Calculate Driver of the Year — best across all monthly winners.
     * Called by scheduler on Jan 1st for the previous year.
     */
    public function calculateYearlyAward(int $year): ?DriverAward
    {
        // Aggregate from all monthly awards that year
        $monthly = DriverAward::where('award_type', 'driver_of_month')
            ->where('year', $year)
            ->get()
            ->groupBy('driver_id')
            ->map(function ($awards, $driverId) {
                return [
                    'driver_id'   => $driverId,
                    'wins'        => $awards->count(),
                    'avg_rating'  => round($awards->avg('average_rating'), 2),
                    'total_trips' => $awards->sum('total_trips'),
                    'score'       => round($awards->sum('score'), 2),
                ];
            })
            ->sortByDesc('score');

        if ($monthly->isEmpty()) {
            // Fallback: compute from raw trips if no monthly data
            return $this->calculateYearlyFromRaw($year);
        }

        $winner = $monthly->first();

        DriverAward::updateOrCreate(
            ['award_type' => 'driver_of_year', 'year' => $year, 'month' => null],
            [
                'driver_id'      => $winner['driver_id'],
                'average_rating' => $winner['avg_rating'],
                'total_trips'    => $winner['total_trips'],
                'score'          => $winner['score'],
            ]
        );

        return DriverAward::where('award_type', 'driver_of_year')
            ->where('year', $year)->first();
    }

    private function calculateYearlyFromRaw(int $year): ?DriverAward
    {
        $start = Carbon::create($year, 1, 1)->startOfYear();
        $end   = $start->copy()->endOfYear();

        $drivers = User::where('role', 'driver')->get();

        $scores = $drivers->map(function (User $driver) use ($start, $end) {
            $trips = Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $avgRating = DriverRating::where('driver_id', $driver->id)
                ->whereBetween('created_at', [$start, $end])
                ->avg('rating') ?? 0;

            return [
                'driver'     => $driver,
                'trips'      => $trips,
                'avg_rating' => round($avgRating, 2),
                'score'      => round(($avgRating * 20) + $trips, 2),
            ];
        })->filter(fn($d) => $d['trips'] > 0)->sortByDesc('score');

        if ($scores->isEmpty()) return null;

        $winner = $scores->first();

        DriverAward::updateOrCreate(
            ['award_type' => 'driver_of_year', 'year' => $year, 'month' => null],
            [
                'driver_id'      => $winner['driver']->id,
                'average_rating' => $winner['avg_rating'],
                'total_trips'    => $winner['trips'],
                'score'          => $winner['score'],
            ]
        );

        return DriverAward::where('award_type', 'driver_of_year')
            ->where('year', $year)->first();
    }

    /** Get ranked leaderboard for current month */
    public function getMonthlyLeaderboard(?int $year = null, ?int $month = null): \Illuminate\Support\Collection
    {
        $year  = $year  ?? now()->year;
        $month = $month ?? now()->month;
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return User::where('role', 'driver')->get()->map(function (User $driver) use ($start, $end) {
            $trips = Trip::where('driver_id', $driver->id)
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$start, $end])
                ->count();

            $avgRating = DriverRating::where('driver_id', $driver->id)
                ->whereBetween('created_at', [$start, $end])
                ->avg('rating') ?? 0;

            return [
                'driver'     => $driver,
                'trips'      => $trips,
                'avg_rating' => round($avgRating, 2),
                'score'      => round(($avgRating * 20) + $trips, 2),
            ];
        })->sortByDesc('score')->values();
    }
}
