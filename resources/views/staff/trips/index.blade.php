@extends('layouts.staff')
@section('page-title', 'My Trip Bookings')

@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('book-modal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Book a Trip
</button>
@endsection

@section('content')

{{-- STAT CARDS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total bookings</div>
        <div class="stat-value text-accent">{{ $stats['total'] }}</div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending approval</div>
        <div class="stat-value text-amber">{{ $stats['pending'] }}</div>
        @if($stats['pending'] > 0)
            <span class="stat-badge badge-amber">Awaiting admin</span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed trips</div>
        <div class="stat-value text-green">{{ $stats['completed'] }}</div>
        <span class="stat-badge badge-green">This month: {{ $stats['completed_month'] }}</span>
    </div>
    <div class="stat-card">
        <div class="stat-label">Awaiting rating</div>
        <div class="stat-value text-purple">{{ $stats['unrated'] }}</div>
        @if($stats['unrated'] > 0)
            <span class="stat-badge badge-purple">Please rate</span>
        @endif
    </div>
</div>

{{-- UNRATED TRIPS BANNER --}}
@if($unratedTrips->count())
<div class="alert alert-amber" style="flex-direction:column;align-items:flex-start;gap:10px">
    <div style="display:flex;align-items:center;gap:8px;font-weight:500">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
        You have {{ $unratedTrips->count() }} completed trip(s) waiting for your rating.
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @foreach($unratedTrips as $ut)
        <button class="btn btn-amber btn-sm"
            onclick="openRatingModal({{ $ut->id }}, '{{ addslashes($ut->driver->name) }}', '{{ $ut->trip_code }}')">
            ★ Rate {{ $ut->driver->name }} — #{{ $ut->trip_code }}
        </button>
        @endforeach
    </div>
</div>
@endif

{{-- ACTIVE TRIP BANNER --}}
@if($activeTrip)
<div class="card" style="border-color:rgba(45,212,191,.35);margin-bottom:20px">
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between">
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--teal)">
                🚗 Trip in progress — #{{ $activeTrip->trip_code }}
            </div>
            <div class="trip-meta" style="margin-top:4px">
                Driver: <strong>{{ $activeTrip->driver->name }}</strong>
                · Vehicle: {{ $activeTrip->vehicle->plate_number }}
                · Started {{ $activeTrip->started_at?->diffForHumans() }}
            </div>
            @if($activeTrip->destination)
            <div class="trip-meta">📍 {{ $activeTrip->destination }}</div>
            @endif
        </div>
        <span class="status-pill status-in_progress">In progress</span>
    </div>
</div>
@endif

{{-- FILTERS + TABLE --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">All my bookings</div>
            <div class="card-sub">{{ $trips->total() }} total trips</div>
        </div>
        <form method="GET" action="{{ route('staff.trips.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <div class="search-wrap">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input class="search-input" name="search" placeholder="Search…" value="{{ request('search') }}" style="width:160px">
            </div>
            <select name="status" class="form-select" style="width:140px">
                <option value="">All statuses</option>
                @foreach(['pending','approved','in_progress','completed','rejected','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>
                    {{ ucfirst(str_replace('_',' ',$s)) }}
                </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
            @if(request('status') || request('search'))
                <a href="{{ route('staff.trips.index') }}" class="btn btn-ghost btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Trip ID</th>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Reason</th>
                <th>Destination</th>
                <th>Scheduled</th>
                <th>Status</th>
                <th>Rating</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trips as $trip)
            <tr>
                <td>
                    <div class="trip-code">#{{ $trip->trip_code }}</div>
                    <div style="font-size:10px;color:var(--text3);margin-top:2px">
                        {{ $trip->created_at->format('d M Y') }}
                    </div>
                </td>
                <td>
                    <div class="td-name">{{ $trip->driver->name }}</div>
                    @if($trip->driver->average_rating > 0)
                    <div style="font-size:11px;color:var(--amber)">★ {{ $trip->driver->average_rating }}</div>
                    @endif
                </td>
                <td>
                    <div style="font-size:12px;font-family:'DM Mono',monospace">{{ $trip->vehicle->plate_number }}</div>
                    <div style="font-size:11px;color:var(--text3)">
                        {{ str_replace('_',' ',ucfirst($trip->vehicle->type)) }}
                    </div>
                </td>
                <td style="max-width:180px">
                    <div style="font-size:13px">{{ Str::limit($trip->reason, 50) }}</div>
                    @if(strlen($trip->reason) > 50)
                    <button class="btn-link"
                        onclick="openReasonModal('{{ addslashes($trip->reason) }}','{{ $trip->trip_code }}')">
                        read more
                    </button>
                    @endif
                </td>
                <td class="text-muted" style="font-size:12px">{{ $trip->destination ?? '—' }}</td>
                <td>
                    <div style="font-size:13px">{{ $trip->scheduled_at->format('d M Y') }}</div>
                    <div style="font-size:11px;color:var(--text3)">{{ $trip->scheduled_at->format('H:i') }}</div>
                </td>
                <td>
                    <span class="status-pill status-{{ $trip->status }}">
                        {{ ucfirst(str_replace('_',' ',$trip->status)) }}
                    </span>
                    @if($trip->status === 'rejected' && $trip->rejection_reason)
                    <div style="font-size:11px;color:var(--red);margin-top:3px;cursor:pointer"
                        onclick="openRejectReasonModal('{{ addslashes($trip->rejection_reason) }}')">
                        See reason ↗
                    </div>
                    @endif
                </td>
                <td>
                    @if($trip->rating)
                        <div style="color:var(--amber);font-size:14px;letter-spacing:1px">
                            {{ str_repeat('★', $trip->rating->rating) }}{{ str_repeat('☆', 5 - $trip->rating->rating) }}
                        </div>
                        @if($trip->rating->comment)
                        <div style="font-size:11px;color:var(--text3);margin-top:2px;max-width:100px;
                            overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="{{ $trip->rating->comment }}">
                            "{{ $trip->rating->comment }}"
                        </div>
                        @endif
                    @elseif($trip->canBeRated())
                        <button class="btn btn-amber btn-sm"
                            onclick="openRatingModal({{ $trip->id }},'{{ addslashes($trip->driver->name) }}','{{ $trip->trip_code }}')">
                            ★ Rate
                        </button>
                    @else
                        <span class="text-muted" style="font-size:12px">—</span>
                    @endif
                </td>
                <td>
                    @if($trip->isPending())
                    <form method="POST" action="{{ route('staff.trips.cancel', $trip) }}"
                        onsubmit="return confirm('Cancel trip #{{ $trip->trip_code }}? This cannot be undone.')">
                        @csrf
                        <button class="btn btn-red btn-sm">Cancel</button>
                    </form>
                    @else
                    <span class="text-muted" style="font-size:12px">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9">
                    <div style="padding:48px;text-align:center">
                        <div style="font-size:40px;margin-bottom:12px">🚗</div>
                        <div style="font-size:14px;font-weight:500;margin-bottom:6px">No trips booked yet</div>
                        <div style="font-size:13px;color:var(--text3);margin-bottom:18px">
                            Book your first trip to get started
                        </div>
                        <button class="btn btn-primary" onclick="openModal('book-modal')">
                            Book a trip now
                        </button>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding:14px 20px;border-top:1px solid var(--border)">
        {{ $trips->withQueryString()->links() }}
    </div>
</div>

{{-- ════════════════════════════════════════════════════
     BOOK TRIP MODAL
════════════════════════════════════════════════════ --}}
<div class="modal-bg" id="book-modal">
    <div class="modal" style="width:580px;max-height:92vh;overflow-y:auto">
        <div class="modal-title">Book a trip</div>
        <div class="modal-sub">
            @if(auth()->user()->isMarketer())
                As a marketer, provide a detailed reason. Admin will approve before the driver is notified.
            @else
                Your request goes to admin for approval before the driver is notified to proceed.
            @endif
        </div>

        <form method="POST" action="{{ route('staff.trips.store') }}" id="book-form">
            @csrf

            {{-- Driver --}}
            <div class="form-group">
                <label class="form-label">Select driver <span style="color:var(--red)">*</span></label>
                <select name="driver_id" class="form-select" required id="driver-select"
                    onchange="updateDriverPreview(this)">
                    <option value="">Choose a driver…</option>
                    @foreach($drivers as $d)
                    <option value="{{ $d->id }}"
                        data-vehicle-id="{{ $d->assignedVehicle?->id }}"
                        data-plate="{{ $d->assignedVehicle?->plate_number }}"
                        data-vtype="{{ str_replace('_',' ',ucfirst($d->assignedVehicle?->type ?? '')) }}"
                        data-status="{{ $d->driverProfile?->status }}"
                        data-rating="{{ $d->average_rating }}">
                        {{ $d->name }}
                        @if($d->assignedVehicle) — {{ $d->assignedVehicle->plate_number }} @endif
                        @if($d->average_rating > 0) · ★ {{ $d->average_rating }} @endif
                        @if(($d->driverProfile?->status ?? 'available') !== 'available')
                            [{{ ucfirst(str_replace('_',' ',$d->driverProfile->status)) }}]
                        @endif
                    </option>
                    @endforeach
                </select>

                {{-- Driver info preview --}}
                <div id="driver-preview"
                    style="display:none;margin-top:10px;padding:12px 14px;background:var(--bg3);border-radius:8px;border:1px solid var(--border2)">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div id="dp-avatar"
                            style="width:38px;height:38px;border-radius:50%;background:rgba(79,124,255,.15);
                            color:var(--accent);display:flex;align-items:center;justify-content:center;
                            font-size:13px;font-weight:600;flex-shrink:0">—</div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:500" id="dp-name">—</div>
                            <div style="font-size:11px;color:var(--text3)">
                                <span id="dp-plate">—</span> · <span id="dp-vtype">—</span>
                            </div>
                        </div>
                        <div style="text-align:right">
                            <div id="dp-rating" style="color:var(--amber);font-size:13px">—</div>
                            <div id="dp-status" style="font-size:11px;margin-top:2px">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Vehicle <span style="color:var(--red)">*</span></label>
                    <select name="vehicle_id" class="form-select" required id="vehicle-select">
                        <option value="">Select vehicle…</option>
                        @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->plate_number }} — {{ str_replace('_',' ',ucfirst($v->type)) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date & time <span style="color:var(--red)">*</span></label>
                    <input type="datetime-local" name="scheduled_at" class="form-input" required
                        min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                        value="{{ old('scheduled_at') }}">
                </div>

                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-input"
                        placeholder="e.g. Victoria Island, NSIA Abuja Branch"
                        value="{{ old('destination') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Reason for trip <span style="color:var(--red)">*</span>
                    @if(auth()->user()->isMarketer())
                    <span style="font-weight:400;font-size:10px;color:var(--amber);margin-left:6px">
                        Marketers: min 20 chars — be specific
                    </span>
                    @endif
                </label>
                <textarea name="reason" class="form-textarea" id="reason-field" required rows="4"
                    minlength="{{ auth()->user()->isMarketer() ? 20 : 10 }}"
                    oninput="updateCharCount(this)"
                    placeholder="{{ auth()->user()->isMarketer()
                        ? 'e.g. Marketing field visit to prospect clients at Ajah — presenting new NSIA life insurance products to 3 companies…'
                        : 'e.g. Client meeting at our Ikoyi branch for policy review and renewal discussion…' }}">{{ old('reason') }}</textarea>
                <div style="display:flex;justify-content:space-between;margin-top:4px">
                    <span style="font-size:11px;color:var(--text3)">
                        Minimum {{ auth()->user()->isMarketer() ? 20 : 10 }} characters required
                    </span>
                    <span style="font-size:11px;color:var(--text3)" id="char-count">
                        {{ strlen(old('reason','')) }} characters
                    </span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('book-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    Submit for approval
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════════
     RATE DRIVER MODAL
════════════════════════════════════════════════════ --}}
<div class="modal-bg" id="rating-modal">
    <div class="modal" style="width:460px">
        <div class="modal-title">Rate your driver</div>
        <div class="modal-sub" id="rating-modal-sub">Trip #—</div>

        <form method="POST" id="rating-form">
            @csrf

            <div class="form-group">
                <label class="form-label">Your rating <span style="color:var(--red)">*</span></label>
                <div id="star-row" style="display:flex;gap:8px;margin-bottom:8px">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button" class="star-btn" data-val="{{ $i }}"
                        onclick="setRating({{ $i }})"
                        style="font-size:38px;background:none;border:none;cursor:pointer;
                            color:var(--border2);transition:all .15s;padding:0;line-height:1">★</button>
                    @endfor
                </div>
                <input type="hidden" name="rating" id="rating-input" required>
                <div id="rating-label"
                    style="font-size:13px;font-weight:500;height:18px;transition:color .15s"></div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Comment
                    <span style="font-weight:400;color:var(--text3)">(optional)</span>
                </label>
                <textarea name="comment" class="form-textarea" rows="3"
                    placeholder="How was your experience? Was the driver punctual, professional, and safe?"></textarea>
            </div>

            <div style="background:var(--bg3);border-radius:8px;padding:12px 14px;margin-bottom:4px">
                <div style="font-size:12px;color:var(--text3);line-height:1.6">
                    Your rating contributes to the monthly leaderboard and helps determine the
                    <strong style="color:var(--text)">Driver of the Month</strong> award.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('rating-modal')">
                    Skip for now
                </button>
                <button type="submit" class="btn btn-primary" id="submit-rating-btn" disabled>
                    Submit rating
                </button>
            </div>
        </form>
    </div>
</div>

{{-- FULL REASON MODAL --}}
<div class="modal-bg" id="reason-modal">
    <div class="modal">
        <div class="modal-title">Trip reason — <span id="reason-trip-code" style="color:var(--text3)"></span></div>
        <div style="margin-top:14px;background:var(--bg3);border-radius:8px;padding:14px 16px;
            font-size:13px;line-height:1.8;color:var(--text)" id="reason-full-text"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('reason-modal')">Close</button>
        </div>
    </div>
</div>

{{-- REJECTION REASON MODAL --}}
<div class="modal-bg" id="reject-reason-modal">
    <div class="modal">
        <div class="modal-title" style="color:var(--red)">Trip rejected — reason</div>
        <div style="margin-top:14px;background:rgba(240,75,75,.08);border:1px solid rgba(240,75,75,.2);
            border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.8;color:var(--red)"
            id="reject-reason-text"></div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('reject-reason-modal')">Close</button>
            <button class="btn btn-primary"
                onclick="closeModal('reject-reason-modal');openModal('book-modal')">
                Book again
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Modal helpers ──────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

// ── Rating modal ───────────────────────────────────────────────
const ratingLabels = {
    1: ['Poor',        'var(--red)'],
    2: ['Fair',        'var(--amber)'],
    3: ['Good',        'var(--amber)'],
    4: ['Very good',   'var(--green)'],
    5: ['Excellent! 🎉','var(--green)'],
};

function openRatingModal(tripId, driverName, tripCode) {
    document.getElementById('rating-form').action = `/staff/trips/${tripId}/rate`;
    document.getElementById('rating-modal-sub').textContent =
        `Driver: ${driverName}  ·  Trip #${tripCode}`;
    // reset
    document.getElementById('rating-input').value = '';
    document.getElementById('rating-label').textContent = 'Tap a star to rate';
    document.getElementById('rating-label').style.color = 'var(--text3)';
    document.getElementById('submit-rating-btn').disabled = true;
    document.querySelectorAll('.star-btn').forEach(b => {
        b.style.color = 'var(--border2)';
        b.style.transform = 'scale(1)';
    });
    openModal('rating-modal');
}

function setRating(val) {
    document.getElementById('rating-input').value = val;
    const [label, color] = ratingLabels[val];
    document.getElementById('rating-label').textContent = label;
    document.getElementById('rating-label').style.color = color;
    document.getElementById('submit-rating-btn').disabled = false;
    document.querySelectorAll('.star-btn').forEach(b => {
        const active = parseInt(b.dataset.val) <= val;
        b.style.color     = active ? 'var(--amber)' : 'var(--border2)';
        b.style.transform = active ? 'scale(1.15)'  : 'scale(1)';
    });
}

// Hover effect
document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('mouseenter', () => {
        const h = parseInt(btn.dataset.val);
        document.querySelectorAll('.star-btn').forEach(b => {
            b.style.color = parseInt(b.dataset.val) <= h ? 'var(--amber)' : 'var(--border2)';
        });
    });
    btn.addEventListener('mouseleave', () => {
        const cur = parseInt(document.getElementById('rating-input').value) || 0;
        document.querySelectorAll('.star-btn').forEach(b => {
            b.style.color = parseInt(b.dataset.val) <= cur ? 'var(--amber)' : 'var(--border2)';
        });
    });
});

// ── Driver preview ─────────────────────────────────────────────
const statusColors = {
    available:'var(--green)', on_trip:'var(--amber)',
    training:'var(--purple)', off_duty:'var(--text3)', suspended:'var(--red)'
};

function updateDriverPreview(select) {
    const opt = select.options[select.selectedIndex];
    if (!select.value) { document.getElementById('driver-preview').style.display='none'; return; }

    const vId     = opt.dataset.vehicleId;
    const plate   = opt.dataset.plate   || 'No vehicle';
    const vtype   = opt.dataset.vtype   || '—';
    const status  = opt.dataset.status  || 'available';
    const rating  = parseFloat(opt.dataset.rating) || 0;
    const name    = opt.text.split('—')[0].split('·')[0].replace(/\[.*\]/,'').trim();

    // auto-select vehicle
    if (vId) {
        const vs = document.getElementById('vehicle-select');
        for (let o of vs.options) { if (o.value == vId) { o.selected = true; break; } }
    }

    document.getElementById('dp-avatar').textContent =
        name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
    document.getElementById('dp-name').textContent    = name;
    document.getElementById('dp-plate').textContent   = plate;
    document.getElementById('dp-vtype').textContent   = vtype;
    document.getElementById('dp-rating').textContent  = rating > 0 ? '★ ' + rating : 'No ratings yet';

    const sEl = document.getElementById('dp-status');
    sEl.textContent   = status.replace('_',' ');
    sEl.style.color   = statusColors[status] || 'var(--text3)';

    // warn if not available
    const warn = document.getElementById('driver-warning');
    if (warn) warn.remove();
    if (status !== 'available') {
        const div = document.createElement('div');
        div.id = 'driver-warning';
        div.style.cssText = 'margin-top:8px;font-size:12px;color:var(--amber);padding:8px 10px;background:rgba(245,166,35,.08);border-radius:6px;border:1px solid rgba(245,166,35,.2)';
        div.textContent = `⚠️ This driver is currently marked as "${status.replace('_',' ')}". Booking is still allowed — admin will confirm availability.`;
        document.getElementById('driver-preview').after(div);
    }

    document.getElementById('driver-preview').style.display = 'block';
}

// ── Char count ─────────────────────────────────────────────────
function updateCharCount(el) {
    document.getElementById('char-count').textContent = el.value.length + ' characters';
}

// ── Reason modals ──────────────────────────────────────────────
function openReasonModal(text, code) {
    document.getElementById('reason-trip-code').textContent = '#' + code;
    document.getElementById('reason-full-text').textContent  = text;
    openModal('reason-modal');
}
function openRejectReasonModal(text) {
    document.getElementById('reject-reason-text').textContent = text;
    openModal('reject-reason-modal');
}

// Re-open book modal on validation error
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => openModal('book-modal'));
@endif
</script>
@endpush
