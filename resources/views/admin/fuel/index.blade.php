{{-- ════════════════════════════════════════
  admin/fuel/index.blade.php
════════════════════════════════════════ --}}
@extends('layouts.admin')
@section('page-title','Fuel Management')
@section('content')

@if($requests->count())
<div class="alert alert-amber">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    {{ $requests->count() }} pending fuel requests — {{ $vehicles->where('fuel_percent','<',25)->count() }} vehicles critical
</div>
@endif

<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Pending requests</div><div class="stat-value text-amber">{{ $requests->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Critical (&lt;25%)</div><div class="stat-value text-red">{{ $vehicles->where('fuel_percent','<',25)->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Vehicles tracked</div><div class="stat-value text-accent">{{ $vehicles->count() }}</div></div>
    <div class="stat-card"><div class="stat-label">Avg fleet fuel</div><div class="stat-value">{{ $vehicles->count() ? round($vehicles->avg('fuel_percent'),1) : 0 }}%</div></div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Pending fuel requests</div></div>
    @forelse($requests as $req)
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:16px">
        <div style="flex:1">
            <div class="td-name">{{ $req->vehicle->make }} {{ $req->vehicle->model }} — {{ $req->vehicle->plate_number }}</div>
            <div class="trip-meta">Driver: {{ $req->driver->name }} · Requested {{ $req->created_at->diffForHumans() }}</div>
            @if($req->notes)<div class="trip-reason">{{ $req->notes }}</div>@endif
        </div>
        <div style="text-align:center;min-width:80px">
            <div style="font-size:22px;font-weight:700;color:{{ $req->current_level_percent<25?'var(--red)':'var(--amber)' }}">{{ $req->current_level_percent }}%</div>
            <div style="font-size:11px;color:var(--text3)">{{ $req->current_level_litres }}L left</div>
        </div>
        <form method="POST" action="{{ route('admin.fuel.acknowledge', $req) }}">
            @csrf
            <button class="btn btn-green">Acknowledge</button>
        </form>
    </div>
    @empty
    <div class="empty-state">No pending fuel requests.</div>
    @endforelse
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Fleet fuel depletion forecast</div><div class="card-sub">Auto-calculated: current litres ÷ consumption L/hr</div></div>
    <table>
        <thead><tr><th>Vehicle</th><th>Level</th><th>Tank</th><th>Consumption</th><th>Hours left</th><th>Est. empty</th><th>Last refuel</th></tr></thead>
        <tbody>
            @foreach($vehicles->sortBy('fuel_percent') as $v)
            <tr>
                <td>
                    <div class="td-name">{{ $v->make }} {{ $v->model }}</div>
                    <div style="font-family:'DM Mono',monospace;font-size:11px;color:var(--text3)">{{ $v->plate_number }}</div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="fuel-bar-wrap" style="width:80px"><div class="fuel-bar" style="width:{{ $v->fuel_percent }}%;background:{{ $v->fuel_percent<=25?'var(--red)':($v->fuel_percent<=50?'var(--amber)':'var(--green)') }}"></div></div>
                        <span style="font-weight:600;color:{{ $v->fuel_percent<=25?'var(--red)':($v->fuel_percent<=50?'var(--amber)':'var(--green)') }}">{{ $v->fuel_percent }}% · {{ $v->current_fuel_level }}L</span>
                    </div>
                </td>
                <td class="text-muted">{{ $v->tank_capacity }}L</td>
                <td class="text-muted">{{ $v->fuel_consumption_per_hour }} L/hr</td>
                <td class="{{ $v->estimated_hours_remaining < 4 ? 'text-red' : 'text-muted' }}">{{ $v->hours_remaining ?? $v->estimated_hours_remaining }} hrs</td>
                <td class="{{ ($v->estimated_empty_at ?? null) && ($v->estimated_empty_at < now()->addHours(6)) ? 'text-red' : 'text-muted' }}" style="font-size:12px">
                    {{ ($v->estimated_empty_at ?? $v->getEstimatedEmptyAtAttribute())?->format('D d M H:i') ?? '—' }}
                </td>
                <td class="text-muted" style="font-size:12px">
                    {{ $v->fuelLogs->last()?->created_at->diffForHumans() ?? 'Never' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
