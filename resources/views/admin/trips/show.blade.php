@extends('layouts.admin')
@section('page-title', 'Trip — ' . $trip->trip_code)

@section('topbar-actions')
<a href="{{ route('admin.trips.index') }}" class="btn btn-ghost">← Back to trips</a>
@if($trip->isPending())
<form method="POST" action="{{ route('admin.trips.approve', $trip) }}" style="display:inline">
    @csrf
    <button class="btn btn-green">✓ Approve</button>
</form>
<button class="btn btn-red" onclick="openModal('reject-modal')">✕ Reject</button>
@endif
@endsection

@section('content')
<div class="two-col" style="align-items:start">

    {{-- LEFT: Trip details --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">{{ $trip->trip_code }}</div>
                    <div class="card-sub">Created {{ $trip->created_at->diffForHumans() }}</div>
                </div>
                <span class="status-pill status-{{ $trip->status }}">{{ ucfirst(str_replace('_',' ',$trip->status)) }}</span>
            </div>
            <div style="padding:20px">
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Booked by</div>
                        <div class="detail-value">{{ $trip->bookedBy->name }} <span class="badge-role">{{ ucfirst($trip->bookedBy->role) }}</span></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Passenger</div>
                        <div class="detail-value">{{ $trip->passenger->name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Driver</div>
                        <div class="detail-value">{{ $trip->driver->name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vehicle</div>
                        <div class="detail-value">{{ $trip->vehicle->make }} {{ $trip->vehicle->model }} — {{ $trip->vehicle->plate_number }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Scheduled</div>
                        <div class="detail-value">{{ $trip->scheduled_at->format('D, d M Y H:i') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Destination</div>
                        <div class="detail-value">{{ $trip->destination ?? '—' }}</div>
                    </div>
                    @if($trip->started_at)
                    <div class="detail-item">
                        <div class="detail-label">Started</div>
                        <div class="detail-value text-green">{{ $trip->started_at->format('D, d M Y H:i') }}</div>
                    </div>
                    @endif
                    @if($trip->completed_at)
                    <div class="detail-item">
                        <div class="detail-label">Completed</div>
                        <div class="detail-value text-accent">{{ $trip->completed_at->format('D, d M Y H:i') }}</div>
                    </div>
                    @endif
                    @if($trip->approvedBy)
                    <div class="detail-item">
                        <div class="detail-label">Approved by</div>
                        <div class="detail-value">{{ $trip->approvedBy->name }} at {{ $trip->approved_at->format('H:i d M Y') }}</div>
                    </div>
                    @endif
                </div>

                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
                    <div class="detail-label" style="margin-bottom:8px">Reason for trip</div>
                    <div style="background:var(--bg3);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--text);line-height:1.6">{{ $trip->reason }}</div>
                </div>

                @if($trip->rejection_reason)
                <div style="margin-top:12px">
                    <div class="detail-label" style="margin-bottom:8px;color:var(--red)">Rejection reason</div>
                    <div style="background:rgba(240,75,75,.08);border:1px solid rgba(240,75,75,.2);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--red)">{{ $trip->rejection_reason }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Route log (staff bus) --}}
        @if($trip->locations->count())
        <div class="card">
            <div class="card-header"><div class="card-title">Route log</div></div>
            @foreach($trip->locations->groupBy('direction') as $direction => $stops)
            <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                <div class="stat-label" style="margin-bottom:10px">{{ ucfirst($direction) }} route</div>
                <div class="route-timeline">
                    @foreach($stops as $i => $stop)
                    <div class="route-stop {{ $i === $stops->count()-1 ? 'last' : '' }}">
                        <div class="route-dot"></div>
                        <div class="route-content">
                            <div class="route-name">{{ $stop->location_name }}</div>
                            @if($stop->arrived_at)
                            <div class="route-time">{{ $stop->arrived_at->format('H:i') }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- RIGHT: Rating + payment status --}}
    <div>
        @if($trip->rating)
        <div class="card">
            <div class="card-header"><div class="card-title">Driver rating</div></div>
            <div style="padding:20px;text-align:center">
                <div style="font-size:36px;font-weight:700;color:var(--amber)">{{ $trip->rating->rating }}/5</div>
                <div style="color:var(--amber);font-size:20px;letter-spacing:4px;margin:6px 0">
                    {{ str_repeat('★', $trip->rating->rating) }}{{ str_repeat('☆', 5 - $trip->rating->rating) }}
                </div>
                <div style="font-size:13px;color:var(--text2)">Rated by {{ $trip->rating->ratedBy->name }}</div>
                @if($trip->rating->comment)
                <div style="background:var(--bg3);border-radius:8px;padding:12px;margin-top:12px;font-size:13px;color:var(--text2);font-style:italic">"{{ $trip->rating->comment }}"</div>
                @endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><div class="card-title">Payment status</div></div>
            <div style="padding:20px">
                @if($trip->payment_processed)
                <div class="alert alert-green">✓ Payment processed for this trip.</div>
                @else
                <div class="alert alert-amber">Payment pending — will be included in next weekend's payout.</div>
                @endif
                <div class="detail-item" style="margin-top:10px">
                    <div class="detail-label">Driver</div>
                    <div class="detail-value">{{ $trip->driver->name }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Trip rate</div>
                    <div class="detail-value">₦{{ number_format($trip->driver->driverProfile?->per_trip_rate ?? 1200) }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Driver info</div></div>
            <div style="padding:16px 20px">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                    <div class="driver-avatar" style="background:rgba(79,124,255,.15);color:var(--accent)">{{ strtoupper(substr($trip->driver->name,0,2)) }}</div>
                    <div>
                        <div class="td-name">{{ $trip->driver->name }}</div>
                        <div class="text-muted" style="font-size:12px">{{ $trip->driver->employee_id }}</div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">{{ $trip->driver->phone ?? '—' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">License</div>
                    <div class="detail-value">{{ $trip->driver->driverProfile?->license_number ?? '—' }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Avg rating</div>
                    <div class="detail-value text-amber">★ {{ $trip->driver->average_rating }}</div>
                </div>
                <a href="{{ route('admin.drivers.show', $trip->driver) }}" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;margin-top:10px">View driver profile</a>
            </div>
        </div>
    </div>
</div>

{{-- REJECT MODAL --}}
<div class="modal-bg" id="reject-modal">
    <div class="modal">
        <div class="modal-title">Reject trip {{ $trip->trip_code }}</div>
        <form method="POST" action="{{ route('admin.trips.reject', $trip) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Rejection reason</label>
                <textarea name="rejection_reason" class="form-textarea" required placeholder="State why this trip is being rejected…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('reject-modal')">Cancel</button>
                <button type="submit" class="btn btn-red">Reject trip</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.detail-item { padding:10px; background:var(--bg3); border-radius:6px; }
.detail-label { font-size:10px; color:var(--text3); text-transform:uppercase; letter-spacing:0.8px; font-family:'DM Mono',monospace; margin-bottom:4px; }
.detail-value { font-size:13px; color:var(--text); font-weight:500; }
.badge-role { font-size:10px; background:var(--bg4); color:var(--text3); padding:1px 6px; border-radius:4px; margin-left:6px; }
.route-timeline { position:relative; }
.route-stop { display:flex; gap:12px; padding-bottom:14px; position:relative; }
.route-stop:not(.last)::before { content:''; position:absolute; left:7px; top:16px; width:2px; bottom:0; background:var(--border2); }
.route-dot { width:16px; height:16px; border-radius:50%; background:var(--accent); border:2px solid var(--bg); flex-shrink:0; margin-top:2px; }
.route-stop.last .route-dot { background:var(--green); }
.route-name { font-size:13px; font-weight:500; }
.route-time { font-size:11px; color:var(--text3); margin-top:2px; }
</style>
@endpush

@push('scripts')
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));
</script>
@endpush
