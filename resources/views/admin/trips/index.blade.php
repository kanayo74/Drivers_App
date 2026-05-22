@extends('layouts.admin')
@section('page-title', 'Trip Bookings')

@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('book-trip-modal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Book Trip
</button>
@endsection

@section('content')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total this month</div>
        <div class="stat-value text-accent">{{ \App\Models\Trip::whereMonth('created_at', now()->month)->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value text-amber">{{ \App\Models\Trip::pending()->count() }}</div>
        <span class="stat-badge badge-amber">Needs action</span>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value text-green">{{ \App\Models\Trip::where('status','completed')->whereMonth('created_at', now()->month)->count() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Rejected</div>
        <div class="stat-value text-red">{{ \App\Models\Trip::where('status','rejected')->whereMonth('created_at', now()->month)->count() }}</div>
    </div>
</div>

{{-- FILTERS --}}
<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('admin.trips.index') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <div class="search-wrap">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input class="search-input" name="search" placeholder="Search passenger…" value="{{ request('search') }}">
            </div>
            <select name="status" class="form-select" style="width:150px">
                <option value="">All statuses</option>
                @foreach(['pending','approved','in_progress','completed','rejected','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <input type="date" name="date" class="form-input" style="width:160px" value="{{ request('date') }}">
            <select name="vehicle_type" class="form-select" style="width:160px">
                <option value="">All car types</option>
                <option value="staff_bus" {{ request('vehicle_type')==='staff_bus'?'selected':'' }}>Staff Bus</option>
                <option value="marketing_car" {{ request('vehicle_type')==='marketing_car'?'selected':'' }}>Marketing Car</option>
                <option value="executive_car" {{ request('vehicle_type')==='executive_car'?'selected':'' }}>Executive Car</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="{{ route('admin.trips.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        </form>
    </div>
</div>

{{-- TRIPS TABLE --}}
<div class="card">
    <div class="card-header">
        <div><div class="card-title">All bookings</div><div class="card-sub">{{ $trips->total() }} total trips</div></div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('admin.trips.index') }}?status=pending" class="btn btn-amber btn-sm">
                Pending ({{ \App\Models\Trip::pending()->count() }})
            </a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Trip ID</th>
                <th>Booked by</th>
                <th>Passenger</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Reason</th>
                <th>Scheduled</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trips as $trip)
            <tr>
                <td><a href="{{ route('admin.trips.show', $trip) }}" class="trip-code">#{{ $trip->trip_code }}</a></td>
                <td class="td-name">{{ $trip->bookedBy->name }}</td>
                <td>{{ $trip->passenger->name }}</td>
                <td>{{ $trip->driver->name }}</td>
                <td>
                    <div style="font-size:12px">{{ $trip->vehicle->plate_number }}</div>
                    <div style="font-size:11px;color:var(--text3)">{{ str_replace('_',' ',ucfirst($trip->vehicle->type)) }}</div>
                </td>
                <td style="max-width:180px">
                    <div style="font-size:13px">{{ Str::limit($trip->reason, 50) }}</div>
                    @if($trip->destination)
                    <div style="font-size:11px;color:var(--text3)">📍 {{ $trip->destination }}</div>
                    @endif
                </td>
                <td>
                    <div style="font-size:13px">{{ $trip->scheduled_at->format('d M Y') }}</div>
                    <div style="font-size:11px;color:var(--text3)">{{ $trip->scheduled_at->format('H:i') }}</div>
                </td>
                <td><span class="status-pill status-{{ $trip->status }}">{{ ucfirst(str_replace('_',' ',$trip->status)) }}</span></td>
                <td>
                    <div class="action-row">
                        <a href="{{ route('admin.trips.show', $trip) }}" class="icon-btn" title="View">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        @if($trip->isPending())
                        <form method="POST" action="{{ route('admin.trips.approve', $trip) }}" style="display:inline">
                            @csrf
                            <button class="icon-btn" title="Approve" style="color:var(--green);border-color:rgba(34,201,122,.3)">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                        </form>
                        <button class="icon-btn" title="Reject" style="color:var(--red);border-color:rgba(240,75,75,.3)" onclick="openRejectModal({{ $trip->id }})">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty-state">No trips found matching your filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:16px 20px;border-top:1px solid var(--border)">
        {{ $trips->withQueryString()->links() }}
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
                    <label class="form-label">Passenger (booking for)</label>
                    <select name="passenger_id" class="form-select" required>
                        <option value="">Select staff…</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign driver</label>
                    <select name="driver_id" class="form-select" required>
                        <option value="">Select driver…</option>
                        @foreach($drivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} — {{ $d->assignedVehicle?->plate_number ?? 'No vehicle' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vehicle</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">Select vehicle…</option>
                        @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->plate_number }} — {{ str_replace('_',' ',ucfirst($v->type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Scheduled date & time</label>
                    <input type="datetime-local" name="scheduled_at" class="form-input" required>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-input" placeholder="e.g. Victoria Island, Lagos">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Reason for trip <span style="color:var(--red)">*</span></label>
                    <textarea name="reason" class="form-textarea" required placeholder="Describe the purpose of this trip clearly…" rows="3"></textarea>
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
        <div class="modal-sub">Provide a reason — it will be shown to the driver and passenger.</div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Rejection reason <span style="color:var(--red)">*</span></label>
                <textarea name="rejection_reason" class="form-textarea" required placeholder="e.g. Vehicle unavailable, duplicate booking…"></textarea>
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
function openRejectModal(tripId) {
    document.getElementById('reject-form').action = `/admin/trips/${tripId}/reject`;
    openModal('reject-modal');
}
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));
</script>
@endpush
