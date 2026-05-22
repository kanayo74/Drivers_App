@extends('layouts.admin')
@section('page-title', 'Drivers')

@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('add-driver-modal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Driver
</button>
@endsection

@section('content')

<div class="driver-grid">
    @forelse($drivers as $driver)
    <div class="driver-card">
        <div class="driver-card-top">
            <div class="driver-avatar" style="background:rgba(79,124,255,.15);color:var(--accent)">
                {{ strtoupper(substr($driver->name, 0, 2)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div class="driver-name">{{ $driver->name }}</div>
                <div class="driver-id">{{ $driver->employee_id }}</div>
            </div>
            @php
                $statusColors = ['available'=>'green','on_trip'=>'teal','off_duty'=>'amber','training'=>'purple','suspended'=>'red'];
                $sc = $statusColors[$driver->driverProfile?->status ?? 'available'] ?? 'green';
            @endphp
            <span class="status-pill status-{{ $sc === 'green' ? 'approved' : ($sc === 'red' ? 'rejected' : ($sc === 'teal' ? 'in_progress' : ($sc === 'purple' ? 'training' : 'pending'))) }}">
                {{ ucfirst(str_replace('_',' ', $driver->driverProfile?->status ?? 'available')) }}
            </span>
        </div>

        <div class="driver-stats">
            <div class="driver-stat">
                <div class="driver-stat-label">Trips</div>
                <div class="driver-stat-val text-accent">{{ $driver->completed_trips }}</div>
            </div>
            <div class="driver-stat">
                <div class="driver-stat-label">Rating</div>
                <div class="driver-stat-val text-amber">{{ $driver->avg_rating > 0 ? $driver->avg_rating . ' ★' : 'N/A' }}</div>
            </div>
            <div class="driver-stat">
                <div class="driver-stat-label">Vehicle</div>
                <div class="driver-stat-val" style="font-size:12px">{{ $driver->assignedVehicle?->plate_number ?? 'None' }}</div>
            </div>
            <div class="driver-stat">
                <div class="driver-stat-label">Owed</div>
                <div class="driver-stat-val text-teal" style="font-size:13px">₦{{ number_format($driver->outstanding) }}</div>
            </div>
        </div>

        <div style="display:flex;gap:6px;margin-top:12px">
            <a href="{{ route('admin.drivers.show', $driver) }}" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center">Profile</a>
            <button class="btn btn-amber btn-sm" style="flex:1;justify-content:center" onclick="openTrainingModal({{ $driver->id }}, '{{ $driver->name }}')">
                Training
            </button>
            <button class="btn btn-ghost btn-sm" onclick="openStatusModal({{ $driver->id }}, '{{ $driver->name }}', '{{ $driver->driverProfile?->status }}')">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
            </button>
        </div>
    </div>
    @empty
    <div class="empty-state" style="grid-column:1/-1">No drivers registered yet.</div>
    @endforelse
</div>

{{-- ADD DRIVER MODAL --}}
<div class="modal-bg" id="add-driver-modal">
    <div class="modal" style="width:560px;max-height:90vh;overflow-y:auto">
        <div class="modal-title">Add new driver</div>
        <div class="modal-sub">Default password will be <code>nsia@driver123</code></div>
        <form method="POST" action="{{ route('admin.drivers.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Full name</label><input type="text" name="name" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Employee ID</label><input type="text" name="employee_id" class="form-input"></div>
                <div class="form-group"><label class="form-label">License number</label><input type="text" name="license_number" class="form-input" required style="font-family:'DM Mono',monospace"></div>
                <div class="form-group"><label class="form-label">License expiry</label><input type="date" name="license_expiry" class="form-input" required></div>
                <div class="form-group">
                    <label class="form-label">License class</label>
                    <select name="license_class" class="form-select" required>
                        @foreach(['A','B','C','D','E'] as $c)<option value="{{ $c }}">Class {{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Years experience</label><input type="number" name="years_experience" class="form-input" min="0" required></div>
                <div class="form-group" style="grid-column:1/-1"><label class="form-label">Per-trip rate (₦)</label><input type="number" name="per_trip_rate" class="form-input" value="1200" required></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('add-driver-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create driver account</button>
            </div>
        </form>
    </div>
</div>

{{-- UPDATE STATUS MODAL --}}
<div class="modal-bg" id="status-modal">
    <div class="modal">
        <div class="modal-title">Update driver status</div>
        <div class="modal-sub" id="status-modal-sub"></div>
        <form method="POST" id="status-form">
            @csrf
            <div class="form-group">
                <label class="form-label">New status</label>
                <select name="status" class="form-select" id="status-select">
                    <option value="available">Available</option>
                    <option value="on_trip">On Trip</option>
                    <option value="off_duty">Off Duty</option>
                    <option value="training">Training</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('status-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

{{-- ASSIGN TRAINING MODAL --}}
<div class="modal-bg" id="training-modal">
    <div class="modal">
        <div class="modal-title">Assign training</div>
        <div class="modal-sub" id="training-modal-sub"></div>
        <form method="POST" action="{{ route('admin.training.store') }}">
            @csrf
            <input type="hidden" name="driver_id" id="training-driver-id">
            <div class="form-group">
                <label class="form-label">Training type</label>
                <select name="training_type" class="form-select" required>
                    <option>Defensive driving</option>
                    <option>Customer service</option>
                    <option>First aid</option>
                    <option>Vehicle handling</option>
                    <option>Road safety</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Provider / Instructor</label><input type="text" name="provider" class="form-input" placeholder="e.g. LASDRI, Red Cross…"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Training date</label><input type="date" name="training_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Duration (days)</label><input type="number" name="duration_days" class="form-input" value="1" min="1" required></div>
            </div>
            <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-textarea" placeholder="Additional details…"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('training-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign & Notify Driver</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); }));

function openTrainingModal(id, name) {
    document.getElementById('training-driver-id').value = id;
    document.getElementById('training-modal-sub').textContent = `Assigning training to ${name}`;
    openModal('training-modal');
}
function openStatusModal(id, name, current) {
    document.getElementById('status-form').action = `/admin/drivers/${id}/status`;
    document.getElementById('status-modal-sub').textContent = `Update status for ${name}`;
    document.getElementById('status-select').value = current || 'available';
    openModal('status-modal');
}
</script>
@endpush
