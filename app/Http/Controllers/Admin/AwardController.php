<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverAward;
use App\Services\AwardService;
use Illuminate\Http\Request;

class AwardController extends Controller
{
    public function __construct(private AwardService $awardService) {}

    public function index()
    {
        $leaderboard    = $this->awardService->getMonthlyLeaderboard();
        $monthlyWinner  = DriverAward::where('award_type', 'driver_of_month')
            ->where('year', now()->year)->where('month', now()->month)
            ->with('driver')->first();
        $yearlyAwards   = DriverAward::where('award_type', 'driver_of_year')
            ->with('driver')->orderByDesc('year')->get();
        $monthlyHistory = DriverAward::where('award_type', 'driver_of_month')
            ->where('year', now()->year)->with('driver')->orderBy('month')->get();

        return view('admin.awards.index', compact(
            'leaderboard', 'monthlyWinner', 'yearlyAwards', 'monthlyHistory'
        ));
    }

    public function computeMonth(Request $request)
    {
        $award = $this->awardService->calculateMonthlyAward(
            $request->year  ?? now()->year,
            $request->month ?? now()->month
        );

        return back()->with('success', $award
            ? "Driver of the Month: {$award->driver->name}"
            : 'No eligible drivers this month.');
    }
}
