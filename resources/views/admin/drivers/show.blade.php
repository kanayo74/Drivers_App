@extends('layouts.admin')
@section('page-title', $driver->name)

@section('topbar-actions')
<a href="{{ route('admin.drivers.index') }}" class="btn btn-ghost">← All drivers</a>
<button class="btn btn-amber" onclick="openModal('training-modal')">Assign Training</button>
@endsection

@section('content')
<div class="two-col" style="align-items:start">

    {{-- LEFT: Profile + stats --}}
    <div>
        <div class="card">
            <div style="padding:24px;text-align:center;border-bottom:1px solid var(--border)">
                <div class="driver-avatar" style="width:64px;height:64px;font-size:22px;background:rgba(79,124,255,.15);color:var(--accent);margin:0 auto 12px">
                    {{ strtoupper(substr($driver->name,0,2)) }}
                </div>
                <div style="font-size:18px;font-weight:600">{{ $driver->name }}</div>
                <div style="font-size:13px;color:var(--text3);margin-top:2px">{{ $driver->employee_id }} · Driver</div>
                @php
                    $s = $driver->driverProfile?->status ?? 'available';
                    $cls = match($s){ 'available'=>'status-approved','on_trip'=>'status-in_progress','suspended'=>'status-rejected','training'=>'status-training',default=>'status-pending' };
                @endphp
                <span class="status-pill {{ $cls }}" style="margin-top:8px">{{ ucfirst(str_replace('_',' ',$s)) }}</span>
            </div>
            <div style="padding:20px">
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value" style="font-size:12px">{{ $driver->email }}</div></div>
                    <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value">{{ $driver->phone ?? '—' }}</div></div>
                    <div class="detail-item"><div class="detail-label">License no.</div><div class="detail-value" style="font-family:'DM Mono',monospace;font-size:12px">{{ $driver->driverProfile?->license_number }}</div></div>
                    <div class="detail-item"><div class="detail-label">License expiry</div><div class="detail-value {{ $driver->driverProfile?->license_expiry?->isPast() ? 'text-red' : '' }}">{{ $driver->driverProfile?->license_expiry?->format('d M Y') ?? '—' }}</div></div>
                    <div class="detail-item"><div class="detail-label">License class</div><div class="detail-value">Class {{ $driver->driverProfile?->license_class }}</div></div>
                    <div class="detail-item"><div class="detail-label">Experience</div><div class="detail-value">{{ $driver->driverProfile?->years_experience }} years</div></div>
                    <div class="detail-item"><div class="detail-label">Vehicle</div><div class="detail-value">{{ $driver->assignedVehicle?->plate_number ?? 'None assigned' }}</div></div>
                    <div class="detail-item"><div class="detail-label">Per-trip rate</div><div class="detail-value text-teal">₦{{ number_format($driver->driverProfile?->per_trip_rate ?? 0) }}</div></div>
                </div>
            </div>
        </div>

        <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:16px">
            <div class="stat-card"><div class="stat-label">Total trips</div><div class="stat-value text-accent">{{ $driver->total_trips }}</div></div>
            <div class="stat-card"><div class="stat-label">Avg rating</div><div class="stat-value text-amber">{{ $driver->average_rating > 0 ? $driver->average_rating . ' ★' : 'N/A' }}</div></div>
            <div class="stat-card"><div class="stat-label">Amount owed</div><div class="stat-value text-teal" style="font-size:20px">₦{{ number_format($driver->outstanding_payment) }}</div></div>
            <div class="stat-card"><div class="stat-label">Awards</div><div class="stat-value">{{ $driver->awards->count() }}</div></div>
        </div>

        {{-- Training history --}}
        <div class="card">
            <div class="card-header"><div class="card-title">Training history</div></div>
            @forelse($driver->trainingAssignments as $t)
            <div class="trip-row">
                <div class="trip-row-info">
                    <div class="td-name">{{ $t->training_type }}</div>
                    <div class="trip-meta">{{ $t->provider }} · {{ $t->training_date->format('d M Y') }} · {{ $t->duration_days }} day(s)</div>
                </div>
                <span class="status-pill status-{{ match($t->status){ 'completed'=>'approved','in_progress'=>'in_progress','cancelled'=>'rejected',default=>'pending' } }}">
                    {{ ucfirst($t->status) }}
                </span>
            </div>
            @empty
            <div class="empty-state">No training assigned yet.</div>
            @endforelse
        </div>

        {{-- Awards --}}
        @if($driver->awards->count())
        <div class="card">
            <div class="card-header"><div class="card-title">Awards</div></div>
            @foreach($driver->awards as $award)
            <div class="trip-row">
                <div class="trip-row-info">
                    <div class="td-name">{{ $award->display_title }}</div>
                    <div class="trip-meta">Avg rating {{ $award->average_rating }} ★ · {{ $award->total_trips }} trips · Score {{ $award->score }}</div>
                </div>
                <span style="font-size:20px">{{ $award->award_type === 'driver_of_year' ? '🏆' : '🥇' }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- RIGHT: Trips + Ratings --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent trips</div>
                <span class="stat-badge badge-blue">{{ $recentTrips->count() }} shown</span>
            </div>
            <table>
                <thead><tr><th>Code</th><th>Passenger</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentTrips as $trip)
                    <tr>
                        <td><a href="{{ route('admin.trips.show', $trip) }}" class="trip-code">#{{ $trip->trip_code }}</a></td>
                        <td class="td-name">{{ $trip->passenger->name }}</td>
                        <td class="text-muted">{{ $trip->scheduled_at->format('d M Y') }}</td>
                        <td><span class="status-pill status-{{ $trip->status }}">{{ ucfirst($trip->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-state">No trips yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent ratings</div>
                <span class="stat-badge badge-amber">Avg: {{ $driver->average_rating }} ★</span>
            </div>
            @forelse($recentRatings as $rating)
            <div class="trip-row">
                <div class="trip-row-info">
                    <div style="color:var(--amber);font-size:15px;letter-spacing:2px">
                        {{ str_repeat('★', $rating->rating) }}{{ str_repeat('☆', 5 - $rating->rating) }}
                    </div>
                    @if($rating->comment)
                    <div class="trip-reason" style="margin-top:3px">"{{ $rating->comment }}"</div>
                    @endif
                    <div class="trip-meta">By {{ $rating->ratedBy->name }} · {{ $rating->created_at->diffForHumans() }}</div>
                </div>
                <div style="font-size:22px;font-weight:700;color:var(--amber)">{{ $rating->rating }}</div>
            </div>
            @empty
            <div class="empty-state">No ratings yet.</div>
            @endforelse
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Payment history</div></div>
            @forelse($driver->payments->take(5) as $pay)
            <div class="trip-row">
                <div class="trip-row-info">
                    <div class="td-name">₦{{ number_format($pay->total_amount) }}</div>
                    <div class="trip-meta">{{ $pay->trips_count }} trips · {{ $pay->period_start->format('d M') }} – {{ $pay->period_end->format('d M Y') }}</div>
                    @if($pay->payment_reference)
                    <div class="trip-meta" style="font-family:'DM Mono',monospace;font-size:11px">{{ $pay->payment_reference }}</div>
                    @endif
                </div>
                <span class="status-pill {{ $pay->isPaid() ? 'status-approved' : 'status-pending' }}">{{ ucfirst($pay->status) }}</span>
            </div>
            @empty
            <div class="empty-state">No payments recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- TRAINING MODAL --}}
<div class="modal-bg" id="training-modal">
    <div class="modal">
        <div class="modal-title">Assign training to {{ $driver->name }}</div>
        <form method="POST" action="{{ route('admin.training.store') }}">
            @csrf
            <input type="hidden" name="driver_id" value="{{ $driver->id }}">
            <div class="form-group">
                <label class="form-label">Training type</label>
                <select name="training_type" class="form-select" required>
                    <option>Defensive driving</option><option>Customer service</option>
                    <option>First aid</option><option>Vehicle handling</option><option>Other</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Provider</label><input type="text" name="provider" class="form-input" placeholder="e.g. LASDRI, Internal…"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Date</label><input type="date" name="training_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Duration (days)</label><input type="number" name="duration_days" class="form-input" value="1" min="1" required></div>
            </div>
            <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-textarea"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('training-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign & Notify</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.detail-item { padding:10px; background:var(--bg3); border-radius:6px; }
.detail-label { font-size:10px; color:var(--text3); text-transform:uppercase; letter-spacing:0.8px; font-family:'DM Mono',monospace; margin-bottom:4px; }
.detail-value { font-size:13px; color:var(--text); font-weight:500; }
</style>
@endpush

@push('scripts')
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));
</script>
@endpush
