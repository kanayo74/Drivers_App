@extends('layouts.admin')

@section('page-title', 'Dashboard Overview')

@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('book-trip-modal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Book Trip
</button>
@endsection

@section('content')

{{-- ── STAT CARDS ─────────────────────────────────────────────── --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Active trips today</div>
        <div class="stat-value text-accent">{{ $stats['trips_today'] }}</div>
        <div class="stat-sub">{{ $stats['trips_in_progress'] }} in progress</div>
        <span class="stat-badge badge-green">Live today</span>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending approvals</div>
        <div class="stat-value text-amber">{{ $stats['pending_approvals'] }}</div>
        <div class="stat-sub">Awaiting your review</div>
        @if($stats['pending_approvals'] > 0)
            <span class="stat-badge badge-amber">Requires action</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Fuel requests</div>
        <div class="stat-value text-red">{{ $stats['fuel_requests'] }}</div>
        <div class="stat-sub">{{ $stats['critical_vehicles'] }} critical vehicles</div>
        @if($stats['critical_vehicles'] > 0)
            <span class="stat-badge badge-red">Urgent</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Weekend payout</div>
        <div class="stat-value text-teal">₦{{ number_format($stats['total_outstanding']) }}</div>
        <div class="stat-sub">{{ $stats['drivers_count'] }} drivers</div>
        <span class="stat-badge {{ now()->isWeekend() ? 'badge-green' : 'badge-blue' }}">
            {{ now()->isWeekend() ? 'Window open ✓' : 'Sat / Sun only' }}
        </span>
    </div>
</div>

<div class="two-col">

    {{-- PENDING TRIP APPROVALS --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Pending approvals</div>
                <div class="card-sub">{{ $pendingTrips->count() }} bookings awaiting review</div>
            </div>
            <a href="{{ route('admin.trips.index') }}?status=pending" class="btn btn-ghost btn-sm">
                View all
            </a>
        </div>

        @forelse($pendingTrips->take(5) as $trip)
        <div class="trip-row">
            <div class="trip-row-info">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span class="trip-code">#{{ $trip->trip_code }}</span>
                    <span class="status-pill status-pending">Pending</span>
                </div>
                <div class="td-name">{{ $trip->passenger->name }}</div>
                <div class="trip-meta">
                    Driver: {{ $trip->driver->name }}
                    · {{ $trip->vehicle->plate_number }}
                    · {{ $trip->scheduled_at->format('d M Y H:i') }}
                </div>
                <div class="trip-reason">{{ Str::limit($trip->reason, 70) }}</div>
            </div>
            <div class="trip-row-actions">
                <form method="POST" action="{{ route('admin.trips.approve', $trip) }}">
                    @csrf
                    <button class="btn btn-green btn-sm">✓ Approve</button>
                </form>
                <button class="btn btn-red btn-sm" onclick="openRejectModal({{ $trip->id }})">
                    ✕ Reject
                </button>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div style="font-size:28px;margin-bottom:8px">🎉</div>
            No pending approvals — you're all caught up!
        </div>
        @endforelse
    </div>

    {{-- FUEL ALERTS --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Fuel alerts</div>
                <div class="card-sub">{{ $fuelAlerts->count() }} vehicles need attention</div>
            </div>
            <a href="{{ route('admin.fuel.index') }}" class="btn btn-ghost btn-sm">Manage fuel</a>
        </div>

        <div style="padding:14px 18px">
            @forelse($fuelAlerts as $vehicle)
            <div class="fuel-card {{ $vehicle->fuel_status === 'critical' ? 'border-red' : 'border-amber' }}"
                style="margin-bottom:10px">
                <div style="flex:1">
                    <div class="fuel-car-name">
                        {{ $vehicle->make }} {{ $vehicle->model }}
                        · <span style="color:var(--text3)">{{ $vehicle->plate_number }}</span>
                    </div>
                    <div class="fuel-car-plate">
                        Driver: {{ $vehicle->assignedDriver?->name ?? 'Unassigned' }}
                    </div>
                    <div class="fuel-bar-lg" style="margin-top:8px">
                        <div class="fuel-bar-fill"
                            style="width:{{ $vehicle->fuel_percent }}%;
                            background:{{ $vehicle->fuel_status === 'critical' ? 'var(--red)' : 'var(--amber)' }}">
                        </div>
                    </div>
                    @if($vehicle->estimated_empty_at)
                    <div style="font-size:11px;color:var(--text3);margin-top:4px">
                        Est. empty: {{ $vehicle->estimated_empty_at->diffForHumans() }}
                    </div>
                    @endif
                </div>
                <div class="fuel-detail">
                    <div class="fuel-pct"
                        style="color:{{ $vehicle->fuel_status === 'critical' ? 'var(--red)' : 'var(--amber)' }}">
                        {{ $vehicle->fuel_percent }}%
                    </div>
                    <div class="fuel-eta">{{ $vehicle->current_fuel_level }}L left</div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <div style="font-size:28px;margin-bottom:8px">✅</div>
                All vehicles have sufficient fuel.
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- TODAY'S TRIPS TABLE --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Today's trips</div>
            <div class="card-sub">{{ $todayTrips->count() }} trips — {{ now()->format('D, d M Y') }}</div>
        </div>
        <a href="{{ route('admin.trips.index') }}" class="btn btn-ghost btn-sm">All trips →</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Trip ID</th>
                <th>Passenger</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($todayTrips as $trip)
            <tr>
                <td>
                    <a href="{{ route('admin.trips.show', $trip) }}" class="trip-code">
                        #{{ $trip->trip_code }}
                    </a>
                </td>
                <td class="td-name">{{ $trip->passenger->name }}</td>
                <td>{{ $trip->driver->name }}</td>
                <td>
                    <div style="font-size:12px;font-family:'DM Mono',monospace">{{ $trip->vehicle->plate_number }}</div>
                    <div style="font-size:11px;color:var(--text3)">{{ str_replace('_',' ',ucfirst($trip->vehicle->type)) }}</div>
                </td>
                <td style="max-width:160px">{{ Str::limit($trip->reason, 40) }}</td>
                <td>
                    <span class="status-pill status-{{ $trip->status }}">
                        {{ ucfirst(str_replace('_',' ',$trip->status)) }}
                    </span>
                </td>
                <td class="text-muted" style="font-size:12px">{{ $trip->scheduled_at->format('H:i') }}</td>
                <td>
                    @if($trip->isPending())
                    <div class="action-row">
                        <form method="POST" action="{{ route('admin.trips.approve', $trip) }}">
                            @csrf
                            <button class="btn btn-green btn-sm">Approve</button>
                        </form>
                        <button class="btn btn-red btn-sm" onclick="openRejectModal({{ $trip->id }})">Reject</button>
                    </div>
                    @else
                    <a href="{{ route('admin.trips.show', $trip) }}" class="btn btn-ghost btn-sm">View</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="empty-state">No trips scheduled today.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- DRIVER LEADERBOARD + QUICK STATS --}}
<div class="two-col">

    {{-- Monthly leaderboard --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Driver leaderboard</div>
                <div class="card-sub">{{ now()->format('F Y') }}</div>
            </div>
            <a href="{{ route('admin.awards.index') }}" class="btn btn-ghost btn-sm">Full awards →</a>
        </div>
        <table>
            <thead>
                <tr><th>#</th><th>Driver</th><th>Trips</th><th>Avg rating</th><th>Score</th></tr>
            </thead>
            <tbody>
                @forelse($monthlyLeader as $i => $entry)
                <tr>
                    <td style="font-size:18px;font-weight:700;color:var(--amber)">
                        {{ ['🥇','🥈','🥉'][$i] ?? ($i+1) }}
                    </td>
                    <td class="td-name">{{ $entry['driver']->name }}</td>
                    <td>{{ $entry['trips'] }}</td>
                    <td>
                        @if($entry['avg_rating'] > 0)
                        <span style="color:var(--amber)">★ {{ $entry['avg_rating'] }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="font-weight:600;color:var(--teal)">{{ $entry['score'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">No trips completed this month yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Quick stats --}}
    <div>
        <div class="card" style="margin-bottom:14px">
            <div class="card-header"><div class="card-title">Fleet overview</div></div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div style="padding:12px;background:var(--bg3);border-radius:8px;text-align:center">
                    <div style="font-size:22px;font-weight:600;color:var(--green)">{{ $stats['active_vehicles'] }}</div>
                    <div style="font-size:11px;color:var(--text3);margin-top:2px">Active vehicles</div>
                </div>
                <div style="padding:12px;background:var(--bg3);border-radius:8px;text-align:center">
                    <div style="font-size:22px;font-weight:600;color:var(--accent)">{{ $stats['drivers_count'] }}</div>
                    <div style="font-size:11px;color:var(--text3);margin-top:2px">Total drivers</div>
                </div>
                <div style="padding:12px;background:var(--bg3);border-radius:8px;text-align:center">
                    <div style="font-size:22px;font-weight:600;color:{{ $stats['overdue_services'] > 0 ? 'var(--red)' : 'var(--green)' }}">
                        {{ $stats['overdue_services'] }}
                    </div>
                    <div style="font-size:11px;color:var(--text3);margin-top:2px">Overdue services</div>
                </div>
                <div style="padding:12px;background:var(--bg3);border-radius:8px;text-align:center">
                    <div style="font-size:22px;font-weight:600;color:var(--amber)">{{ $stats['fuel_requests'] }}</div>
                    <div style="font-size:11px;color:var(--text3);margin-top:2px">Fuel requests</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Quick links</div></div>
            <div style="padding:14px 20px;display:flex;flex-direction:column;gap:8px">
                <a href="{{ route('admin.trips.index') }}?status=pending" class="btn btn-amber" style="justify-content:space-between">
                    <span>Pending trips</span>
                    <span style="background:rgba(0,0,0,.2);padding:2px 8px;border-radius:20px;font-size:11px">{{ $stats['pending_approvals'] }}</span>
                </a>
                <a href="{{ route('admin.fuel.index') }}" class="btn btn-red" style="justify-content:space-between">
                    <span>Fuel requests</span>
                    <span style="background:rgba(0,0,0,.2);padding:2px 8px;border-radius:20px;font-size:11px">{{ $stats['fuel_requests'] }}</span>
                </a>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-ghost" style="justify-content:space-between">
                    <span>Driver payments</span>
                    <span style="font-size:11px;color:var(--teal)">₦{{ number_format($stats['total_outstanding']) }}</span>
                </a>
                <a href="{{ route('admin.maintenance.index') }}" class="btn btn-ghost" style="justify-content:space-between">
                    <span>Maintenance</span>
                    @if($stats['overdue_services'] > 0)
                    <span style="color:var(--red);font-size:11px">{{ $stats['overdue_services'] }} overdue</span>
                    @else
                    <span style="color:var(--green);font-size:11px">All good ✓</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</div>

{{-- BOOK TRIP MODAL --}}
<div class="modal-bg" id="book-trip-modal">
    <div class="modal" style="width:560px">
        <div class="modal-title">Book a trip</div>
        <div class="modal-sub">Admin can book on behalf of any staff member</div>
        <form method="POST" action="{{ route('admin.trips.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Passenger (booking for) <span style="color:var(--red)">*</span></label>
                    <select name="passenger_id" class="form-select" required>
                        <option value="">Select staff member…</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign driver <span style="color:var(--red)">*</span></label>
                    <select name="driver_id" class="form-select" required>
                        <option value="">Select driver…</option>
                        @foreach($drivers as $d)
                        <option value="{{ $d->id }}">
                            {{ $d->name }}
                            @if($d->assignedVehicle) — {{ $d->assignedVehicle->plate_number }} @endif
                        </option>
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
                    <label class="form-label">Scheduled date & time <span style="color:var(--red)">*</span></label>
                    <input type="datetime-local" name="scheduled_at" class="form-input" required
                        min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-input" placeholder="e.g. Victoria Island">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Reason for trip <span style="color:var(--red)">*</span></label>
                    <textarea name="reason" class="form-textarea" required rows="3"
                        placeholder="Describe the purpose of this trip clearly…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('book-trip-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Book & Notify Driver</button>
            </div>
        </form>
    </div>
</div>

{{-- REJECT MODAL --}}
<div class="modal-bg" id="reject-modal">
    <div class="modal">
        <div class="modal-title">Reject trip</div>
        <div class="modal-sub">Provide a reason — shown to the driver and passenger.</div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Rejection reason <span style="color:var(--red)">*</span></label>
                <textarea name="rejection_reason" class="form-textarea" required
                    placeholder="e.g. Vehicle unavailable, duplicate booking…" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('reject-modal')">Cancel</button>
                <button type="submit" class="btn btn-red">Confirm Rejection</button>
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
