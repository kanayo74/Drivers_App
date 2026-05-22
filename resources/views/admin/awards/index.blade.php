{{-- admin/awards/index.blade.php --}}
@extends('layouts.admin')
@section('page-title', 'Driver Awards')
@section('topbar-actions')
<form method="POST" action="{{ route('admin.awards.compute-month') }}">
    @csrf
    <input type="hidden" name="year" value="{{ now()->year }}">
    <input type="hidden" name="month" value="{{ now()->month }}">
    <button class="btn btn-primary">Compute this month</button>
</form>
@endsection
@section('content')

<div class="three-col">
    {{-- DRIVER OF THE YEAR --}}
    @if($yearlyAwards->count())
    <div class="award-card">
        <div class="award-crown">🏆</div>
        <div class="award-title">Driver of the Year · {{ $yearlyAwards->first()->year }}</div>
        <div class="award-name">{{ $yearlyAwards->first()->driver->name }}</div>
        <div class="award-score">Avg rating {{ $yearlyAwards->first()->average_rating }} ★ · {{ $yearlyAwards->first()->total_trips }} trips</div>
    </div>
    @endif

    {{-- DRIVER OF THE MONTH --}}
    @if($monthlyWinner)
    <div style="background:linear-gradient(135deg,rgba(34,201,122,.1),rgba(45,212,191,.08));border:1px solid rgba(34,201,122,.25);border-radius:var(--radius);padding:24px;text-align:center">
        <div style="font-size:36px;margin-bottom:8px">🥇</div>
        <div style="font-size:11px;color:var(--green);text-transform:uppercase;letter-spacing:1.5px;font-family:'DM Mono',monospace">Driver of the Month · {{ now()->format('F Y') }}</div>
        <div style="font-size:20px;font-weight:600;margin-top:6px">{{ $monthlyWinner->driver->name }}</div>
        <div style="font-size:13px;color:var(--text3)">Avg rating {{ $monthlyWinner->average_rating }} ★ · {{ $monthlyWinner->total_trips }} trips</div>
    </div>
    @else
    <div class="card" style="margin-bottom:0;display:flex;align-items:center;justify-content:center;min-height:140px">
        <div style="text-align:center">
            <div style="font-size:28px;margin-bottom:6px">🏁</div>
            <div style="font-size:13px;color:var(--text3)">No winner computed yet for {{ now()->format('F Y') }}</div>
        </div>
    </div>
    @endif

    {{-- STATS --}}
    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="stat-card"><div class="stat-label">Monthly winners this year</div><div class="stat-value text-amber">{{ $monthlyHistory->count() }}</div></div>
        <div class="stat-card"><div class="stat-label">Total yearly awards</div><div class="stat-value text-accent">{{ $yearlyAwards->count() }}</div></div>
    </div>
</div>

{{-- MONTHLY LEADERBOARD --}}
<div class="card">
    <div class="card-header">
        <div><div class="card-title">Leaderboard — {{ now()->format('F Y') }}</div><div class="card-sub">Ranked by score (avg rating × 20 + trips)</div></div>
    </div>
    <table>
        <thead><tr><th>Rank</th><th>Driver</th><th>Trips this month</th><th>Avg rating</th><th>Score</th></tr></thead>
        <tbody>
            @forelse($leaderboard as $i => $entry)
            <tr>
                <td style="font-size:18px;font-weight:700;color:var(--amber)">
                    {{ ['🥇','🥈','🥉'][$i] ?? ($i+1) }}
                </td>
                <td class="td-name">{{ $entry['driver']->name }}</td>
                <td>{{ $entry['trips'] }}</td>
                <td><span style="color:var(--amber)">★ {{ $entry['avg_rating'] ?: 'N/A' }}</span></td>
                <td style="font-weight:600;color:var(--teal)">{{ $entry['score'] }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state">No trips completed this month yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- MONTHLY HISTORY THIS YEAR --}}
@if($monthlyHistory->count())
<div class="card">
    <div class="card-header"><div class="card-title">Monthly winners — {{ now()->year }}</div></div>
    <table>
        <thead><tr><th>Month</th><th>Winner</th><th>Avg rating</th><th>Total trips</th><th>Score</th></tr></thead>
        <tbody>
            @foreach($monthlyHistory as $award)
            <tr>
                <td class="td-name">{{ \Carbon\Carbon::create()->month($award->month)->format('F') }}</td>
                <td>{{ $award->driver->name }}</td>
                <td><span style="color:var(--amber)">★ {{ $award->average_rating }}</span></td>
                <td>{{ $award->total_trips }}</td>
                <td style="font-weight:600">{{ $award->score }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ALL-TIME YEARLY --}}
<div class="card">
    <div class="card-header"><div class="card-title">All-time Driver of the Year</div></div>
    <table>
        <thead><tr><th>Year</th><th>Winner</th><th>Avg rating</th><th>Total trips</th><th>Award</th></tr></thead>
        <tbody>
            @forelse($yearlyAwards as $award)
            <tr>
                <td class="td-name">{{ $award->year }}</td>
                <td class="td-name"><a href="{{ route('admin.drivers.show', $award->driver) }}">{{ $award->driver->name }}</a></td>
                <td><span style="color:var(--amber)">★ {{ $award->average_rating }}</span></td>
                <td>{{ $award->total_trips }}</td>
                <td><span class="stat-badge badge-amber">🏆 Winner</span></td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state">No yearly awards yet. Use "Compute this month" at month-end.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
