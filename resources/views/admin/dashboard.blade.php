@extends('layouts.admin')

@section('page-title', 'Dashboard Overview')

@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('book-trip-modal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Book Trip
</button>
@endsection

@push('styles')
<style>
.db-grid4   { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
.db-grid2   { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
.db-grid3   { display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:14px; }
.metric     { background:var(--bg3); border-radius:var(--radius); padding:16px; }
.metric-label{ font-size:11px; color:var(--text3); text-transform:uppercase; letter-spacing:.6px; font-family:'DM Mono',monospace; margin-bottom:8px; }
.metric-val  { font-size:26px; font-weight:600; line-height:1; }
.metric-sub  { font-size:12px; color:var(--text3); margin-top:5px; }
.badge-sm   { display:inline-flex; align-items:center; gap:4px; font-size:11px; padding:3px 8px; border-radius:20px; margin-top:8px; font-weight:500; }
.fuel-bar-track{ height:5px; background:var(--bg4); border-radius:3px; margin:7px 0 3px; overflow:hidden; }
.fuel-bar-inner{ height:100%; border-radius:3px; transition:width .3s; }
.fuel-item  { padding:13px 20px; border-bottom:1px solid var(--border); }
.fuel-item:last-child{ border-bottom:none; }
.sched-row  { display:flex; align-items:center; gap:10px; padding:10px 20px; border-bottom:1px solid var(--border); font-size:13px; }
.sched-row:last-child{ border-bottom:none; }
.sched-time { font-size:11px; font-family:'DM Mono',monospace; color:var(--text3); width:42px; flex-shrink:0; }
.lb-row     { display:flex; align-items:center; gap:10px; padding:11px 20px; border-bottom:1px solid var(--border); }
.lb-row:last-child{ border-bottom:none; }
.lb-rank    { font-size:16px; width:26px; flex-shrink:0; text-align:center; }
.lb-score   { font-size:13px; font-weight:600; color:var(--teal); margin-left:auto; }
.mini-stat  { background:var(--bg3); border-radius:var(--radius-sm); padding:12px 14px; }
.mini-val   { font-size:20px; font-weight:600; }
.mini-lbl   { font-size:11px; color:var(--text3); margin-top:3px; }
.ql-btn     { width:100%; display:flex; align-items:center; justify-content:space-between;
              padding:10px 14px; border-radius:var(--radius-sm); font-size:13px; font-weight:500;
              cursor:pointer; font-family:'DM Sans',sans-serif; border:1px solid var(--border);
              background:transparent; color:var(--text); transition:background .12s; margin-bottom:6px; }
.ql-btn:hover{ background:var(--bg3); }
.ql-btn:last-child{ margin-bottom:0; }
.ql-btn-left{ display:flex; align-items:center; gap:8px; }
@media(max-width:900px){
    .db-grid4{ grid-template-columns:repeat(2,1fr); }
    .db-grid2,.db-grid3{ grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')

{{-- ── METRIC CARDS ─────────────────────────────────────────── --}}
<div class="db-grid4">
    <div class="metric">
        <div class="metric-label">Trips today</div>
        <div class="metric-val text-accent">{{ $stats['trips_today'] }}</div>
        <div class="metric-sub">{{ $stats['trips_in_progress'] }} in progress</div>
        <span class="badge-sm badge-green">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Live
        </span>
    </div>
    <div class="metric">
        <div class="metric-label">Pending approvals</div>
        <div class="metric-val text-amber">{{ $stats['pending_approvals'] }}</div>
        <div class="metric-sub">Awaiting your review</div>
        @if($stats['pending_approvals'] > 0)
        <span class="badge-sm badge-amber">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            Action needed
        </span>
        @endif
    </div>
    <div class="metric">
        <div class="metric-label">Fuel requests</div>
        <div class="metric-val text-red">{{ $stats['fuel_requests'] }}</div>
        <div class="metric-sub">{{ $stats['critical_vehicles'] }} critical vehicles</div>
        @if($stats['critical_vehicles'] > 0)
        <span class="badge-sm badge-red">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L12 12"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
            Urgent
        </span>
        @endif
    </div>
    <div class="metric">
        <div class="metric-label">Weekend payout</div>
        <div class="metric-val text-teal" style="font-size:20px">₦{{ number_format($stats['total_outstanding']) }}</div>
        <div class="metric-sub">{{ $stats['drivers_count'] }} drivers outstanding</div>
        <span class="badge-sm {{ now()->isWeekend() ? 'badge-green' : 'badge-blue' }}">
            {{ now()->isWeekend() ? '✓ Window open' : 'Sat / Sun only' }}
        </span>
    </div>
</div>

{{-- ── PENDING + FUEL ───────────────────────────────────────── --}}
<div class="db-grid2">

    {{-- Pending approvals --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Pending approvals</div>
                <div class="card-sub">{{ $pendingTrips->count() }} bookings awaiting review</div>
            </div>
            <a href="{{ route('admin.trips.index') }}?status=pending" class="btn btn-ghost btn-sm">View all</a>
        </div>

        @forelse($pendingTrips->take(4) as $trip)
        <div class="trip-row">
            <div class="trip-row-info">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:4px">
                    <span class="trip-code">#{{ $trip->trip_code }}</span>
                    <span class="status-pill status-pending">Pending</span>
                </div>
                <div class="td-name">{{ $trip->passenger->name }}</div>
                <div class="trip-meta">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/></svg>
                    {{ $trip->driver->name }} · {{ $trip->vehicle->plate_number }} · {{ $trip->scheduled_at->format('H:i') }}
                </div>
                <div class="trip-reason">"{{ Str::limit($trip->reason, 65) }}"</div>
            </div>
            <div class="trip-row-actions">
                <form method="POST" action="{{ route('admin.trips.approve', $trip) }}">
                    @csrf
                    <button class="btn btn-green btn-sm" title="Approve">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                </form>
                <button class="btn btn-red btn-sm" title="Reject" onclick="openRejectModal({{ $trip->id }})">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        @empty
        <div class="empty-state" style="padding:32px">
            <div style="font-size:26px;margin-bottom:8px">🎉</div>
            No pending approvals — all caught up!
        </div>
        @endforelse
    </div>

    {{-- Fuel alerts --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Fuel alerts</div>
                <div class="card-sub">{{ $fuelAlerts->count() }} vehicles need attention</div>
            </div>
            <a href="{{ route('admin.fuel.index') }}" class="btn btn-ghost btn-sm">Manage</a>
        </div>

        @forelse($fuelAlerts as $vehicle)
        <div class="fuel-item">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                <div style="flex:1;min-width:0">
                    <div class="td-name" style="font-size:13px">
                        {{ $vehicle->make }} {{ $vehicle->model }}
                        <span class="text-muted" style="font-family:'DM Mono',monospace;font-size:11px">· {{ $vehicle->plate_number }}</span>
                    </div>
                    <div class="trip-meta">Driver: {{ $vehicle->assignedDriver?->name ?? 'Unassigned' }}</div>
                    <div class="fuel-bar-track">
                        <div class="fuel-bar-inner"
                            style="width:{{ $vehicle->fuel_percent }}%;
                            background:{{ $vehicle->fuel_percent <= 20 ? 'var(--red)' : 'var(--amber)' }}">
                        </div>
                    </div>
                    @if($vehicle->estimated_empty_at)
                    <div style="font-size:11px;color:var(--text3)">
                        Est. empty {{ $vehicle->estimated_empty_at->diffForHumans() }}
                    </div>
                    @endif
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:18px;font-weight:600;color:{{ $vehicle->fuel_percent <= 20 ? 'var(--red)' : 'var(--amber)' }}">
                        {{ $vehicle->fuel_percent }}%
                    </div>
                    <div style="font-size:11px;color:var(--text3)">{{ $vehicle->current_fuel_level }}L</div>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state" style="padding:32px">
            <div style="font-size:26px;margin-bottom:8px">✅</div>
            All vehicles have sufficient fuel.
        </div>
        @endforelse
    </div>
</div>

{{-- ── TODAY'S SCHEDULE ─────────────────────────────────────── --}}
<div class="card" style="margin-bottom:14px">
    <div class="card-header">
        <div>
            <div class="card-title">Today's schedule</div>
            <div class="card-sub">{{ $todayTrips->count() }} trips · {{ now()->format('D, d M Y') }}</div>
        </div>
        <a href="{{ route('admin.trips.index') }}" class="btn btn-ghost btn-sm">All trips →</a>
    </div>
    @forelse($todayTrips as $trip)
    <div class="sched-row">
        <span class="sched-time">{{ $trip->scheduled_at->format('H:i') }}</span>
        <a href="{{ route('admin.trips.show', $trip) }}" class="trip-code">#{{ $trip->trip_code }}</a>
        <span class="td-name" style="flex:1;font-size:13px">{{ $trip->passenger->name }}</span>
        <span style="font-size:12px;color:var(--text3)">{{ $trip->driver->name }} · {{ $trip->vehicle->plate_number }}</span>
        <span class="status-pill status-{{ $trip->status }}" style="margin-left:10px;flex-shrink:0">
            {{ ucfirst(str_replace('_',' ',$trip->status)) }}
        </span>
        @if($trip->isPending())
        <div class="action-row" style="margin-left:8px;flex-shrink:0">
            <form method="POST" action="{{ route('admin.trips.approve', $trip) }}">
                @csrf
                <button class="btn btn-green btn-sm" title="Approve">✓</button>
            </form>
            <button class="btn btn-red btn-sm" onclick="openRejectModal({{ $trip->id }})">✕</button>
        </div>
        @endif
    </div>
    @empty
    <div class="empty-state">No trips scheduled today.</div>
    @endforelse
</div>

{{-- ── LEADERBOARD + QUICK ACTIONS ─────────────────────────── --}}
<div class="db-grid3">

    {{-- Leaderboard --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Driver leaderboard</div>
                <div class="card-sub">{{ now()->format('F Y') }}</div>
            </div>
            <a href="{{ route('admin.awards.index') }}" class="btn btn-ghost btn-sm">Awards →</a>
        </div>
        @forelse($monthlyLeader as $i => $entry)
        <div class="lb-row">
            <span class="lb-rank">{{ ['🥇','🥈','🥉'][$i] ?? ($i+1) }}</span>
            <div style="flex:1;min-width:0">
                <div class="td-name" style="font-size:13px">{{ $entry['driver']->name }}</div>
                <div class="trip-meta">{{ $entry['trips'] }} trips this month</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                @if($entry['avg_rating'] > 0)
                <div style="font-size:12px;color:var(--amber)">★ {{ $entry['avg_rating'] }}</div>
                @else
                <div class="text-muted" style="font-size:12px">No ratings</div>
                @endif
                <div class="lb-score">{{ $entry['score'] }}</div>
            </div>
        </div>
        @empty
        <div class="empty-state">No trips completed this month yet.</div>
        @endforelse
    </div>

    {{-- Mini stats + Quick links --}}
    <div style="display:flex;flex-direction:column;gap:10px">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div class="mini-stat">
                <div class="mini-val text-green">{{ $stats['active_vehicles'] }}</div>
                <div class="mini-lbl">Active vehicles</div>
            </div>
            <div class="mini-stat">
                <div class="mini-val text-accent">{{ $stats['drivers_count'] }}</div>
                <div class="mini-lbl">Drivers</div>
            </div>
            <div class="mini-stat">
                <div class="mini-val {{ $stats['overdue_services'] > 0 ? 'text-red' : 'text-green' }}">{{ $stats['overdue_services'] }}</div>
                <div class="mini-lbl">Overdue services</div>
            </div>
            <div class="mini-stat">
                <div class="mini-val text-amber">{{ $stats['fuel_requests'] }}</div>
                <div class="mini-lbl">Fuel requests</div>
            </div>
        </div>

        <div class="card" style="padding:14px 16px">
            <div class="card-title" style="margin-bottom:10px;font-size:13px">Quick actions</div>

            <button class="ql-btn" onclick="openModal('book-trip-modal')">
                <span class="ql-btn-left">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Book a trip
                </span>
                <span class="badge-sm badge-amber" style="margin-top:0">{{ $stats['pending_approvals'] }} pending</span>
            </button>

            <a href="{{ route('admin.fuel.index') }}" class="ql-btn">
                <span class="ql-btn-left">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V7l4-4h7l4 4v15"/><line x1="3" y1="11" x2="17" y2="11"/></svg>
                    Fuel requests
                </span>
                <span class="badge-sm badge-red" style="margin-top:0">{{ $stats['fuel_requests'] }} open</span>
            </a>

            <a href="{{ route('admin.payments.index') }}" class="ql-btn">
                <span class="ql-btn-left">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Driver payments
                </span>
                <span style="font-size:11px;color:var(--teal)">₦{{ number_format($stats['total_outstanding']) }}</span>
            </a>

            <a href="{{ route('admin.maintenance.index') }}" class="ql-btn">
                <span class="ql-btn-left">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                    Maintenance
                </span>
                @if($stats['overdue_services'] > 0)
                <span style="font-size:11px;color:var(--red)">{{ $stats['overdue_services'] }} overdue</span>
                @else
                <span style="font-size:11px;color:var(--green)">All good ✓</span>
                @endif
            </a>
        </div>
    </div>
</div>

{{-- ── BOOK TRIP MODAL ──────────────────────────────────────── --}}
<div class="modal-bg" id="book-trip-modal">
    <div class="modal" style="width:560px;max-height:90vh;overflow-y:auto">
        <div class="modal-title">Book a trip</div>
        <div class="modal-sub">Admin can book on behalf of any staff member</div>
        <form method="POST" action="{{ route('admin.trips.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Passenger <span style="color:var(--red)">*</span></label>
                    <select name="passenger_id" class="form-select" required>
                        <option value="">Select staff member…</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Driver <span style="color:var(--red)">*</span></label>
                    <select name="driver_id" class="form-select" required>
                        <option value="">Select driver…</option>
                        @foreach($drivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}@if($d->assignedVehicle) — {{ $d->assignedVehicle->plate_number }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vehicle <span style="color:var(--red)">*</span></label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">Select vehicle…</option>
                        @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->plate_number }} — {{ str_replace('_',' ',ucfirst($v->type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date & time <span style="color:var(--red)">*</span></label>
                    <input type="datetime-local" name="scheduled_at" class="form-input" required
                        min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-input" placeholder="e.g. Victoria Island, Lagos">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Reason for trip <span style="color:var(--red)">*</span></label>
                    <textarea name="reason" class="form-textarea" required rows="3"
                        placeholder="Describe the purpose clearly…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('book-trip-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Book & notify driver
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── REJECT MODAL ─────────────────────────────────────────── --}}
<div class="modal-bg" id="reject-modal">
    <div class="modal" style="width:420px">
        <div class="modal-title">Reject trip</div>
        <div class="modal-sub">Reason will be shown to the driver and passenger.</div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Rejection reason <span style="color:var(--red)">*</span></label>
                <textarea name="rejection_reason" class="form-textarea" required rows="3"
                    placeholder="e.g. Vehicle unavailable, duplicate booking…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('reject-modal')">Cancel</button>
                <button type="submit" class="btn btn-red">Confirm rejection</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
function openRejectModal(tripId) {
    document.getElementById('reject-form').action = `/admin/trips/${tripId}/reject`;
    openModal('reject-modal');
}
</script>
@endpush
