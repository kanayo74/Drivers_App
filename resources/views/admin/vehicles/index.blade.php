{{-- ═══════════════════════════════════════════════════════════
     resources/views/admin/vehicles/index.blade.php
═══════════════════════════════════════════════════════════ --}}
@extends('layouts.admin')
@section('page-title', 'Fleet / Vehicles')
@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('add-vehicle-modal')">+ Add Vehicle</button>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <div><div class="card-title">All vehicles</div><div class="card-sub">{{ $vehicles->count() }} registered</div></div>
    </div>
    <table>
        <thead>
            <tr><th>Vehicle</th><th>Type</th><th>Plate</th><th>Driver</th><th>Fuel</th><th>Est. empty</th><th>Next service</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($vehicles as $v)
            <tr>
                <td class="td-name">{{ $v->make }} {{ $v->model }} <span class="text-muted" style="font-size:11px">({{ $v->year }})</span></td>
                <td>{{ str_replace('_',' ',ucfirst($v->type)) }}</td>
                <td style="font-family:'DM Mono',monospace;font-size:12px">{{ $v->plate_number }}</td>
                <td>{{ $v->assignedDriver?->name ?? '<span class="text-muted">Unassigned</span>' }}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="fuel-bar-wrap"><div class="fuel-bar" style="width:{{ $v->fuel_percent }}%;background:{{ $v->fuel_percent<=25?'var(--red)':($v->fuel_percent<=50?'var(--amber)':'var(--green)') }}"></div></div>
                        <span style="font-size:11px;font-weight:600;color:{{ $v->fuel_percent<=25?'var(--red)':($v->fuel_percent<=50?'var(--amber)':'var(--green)') }}">{{ $v->fuel_percent }}%</span>
                    </div>
                </td>
                <td class="{{ $v->estimated_hours_remaining < 4 ? 'text-red' : 'text-muted' }}" style="font-size:12px">
                    {{ $v->estimated_empty_at?->format('d M H:i') ?? '—' }}
                </td>
                <td class="{{ $v->isServiceDue() ? 'text-red' : 'text-muted' }}" style="font-size:12px">
                    {{ $v->next_service_date?->format('d M Y') ?? '—' }}
                </td>
                <td><span class="status-pill status-{{ match($v->status){'active'=>'approved','in_service'=>'training','inactive'=>'pending',default=>'rejected'} }}">{{ ucfirst(str_replace('_',' ',$v->status)) }}</span></td>
                <td>
                    <div class="action-row">
                        <a href="{{ route('admin.vehicles.show', $v) }}" class="icon-btn" title="View">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <button class="icon-btn" title="Assign driver" onclick="openAssignModal({{ $v->id }}, '{{ $v->plate_number }}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ADD VEHICLE MODAL --}}
<div class="modal-bg" id="add-vehicle-modal">
    <div class="modal" style="width:560px">
        <div class="modal-title">Add new vehicle</div>
        <form method="POST" action="{{ route('admin.vehicles.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Make</label><input type="text" name="make" class="form-input" placeholder="e.g. Toyota" required></div>
                <div class="form-group"><label class="form-label">Model</label><input type="text" name="model" class="form-input" placeholder="e.g. Hiace" required></div>
                <div class="form-group"><label class="form-label">Year</label><input type="text" name="year" class="form-input" placeholder="e.g. 2022" required></div>
                <div class="form-group"><label class="form-label">Plate number</label><input type="text" name="plate_number" class="form-input" placeholder="e.g. LSR-001-KJ" required style="font-family:'DM Mono',monospace"></div>
                <div class="form-group">
                    <label class="form-label">Vehicle type</label>
                    <select name="type" class="form-select" required>
                        <option value="staff_bus">Staff Bus</option>
                        <option value="marketing_car">Marketing Car</option>
                        <option value="executive_car">Executive Car</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Color</label><input type="text" name="color" class="form-input" placeholder="e.g. White"></div>
                <div class="form-group"><label class="form-label">Engine size (L)</label><input type="number" name="engine_size" class="form-input" step="0.1" placeholder="e.g. 2.7" required></div>
                <div class="form-group"><label class="form-label">Tank capacity (L)</label><input type="number" name="tank_capacity" class="form-input" placeholder="e.g. 50" required></div>
                <div class="form-group" style="grid-column:1/-1"><label class="form-label">Fuel consumption (L/hour)</label><input type="number" name="fuel_consumption_per_hour" class="form-input" step="0.1" placeholder="e.g. 2.2" required></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Assign driver (optional)</label>
                    <select name="assigned_driver_id" class="form-select">
                        <option value="">Unassigned</option>
                        @foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('add-vehicle-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add to fleet</button>
            </div>
        </form>
    </div>
</div>

{{-- ASSIGN DRIVER MODAL --}}
<div class="modal-bg" id="assign-modal">
    <div class="modal">
        <div class="modal-title">Assign driver</div>
        <div class="modal-sub" id="assign-modal-sub"></div>
        <form method="POST" id="assign-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Select driver</label>
                <select name="driver_id" class="form-select" required>
                    <option value="">Choose driver…</option>
                    @foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('assign-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign</button>
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
function openAssignModal(id, plate) {
    document.getElementById('assign-form').action = `/admin/vehicles/${id}/assign-driver`;
    document.getElementById('assign-modal-sub').textContent = `Assign a driver to ${plate}`;
    openModal('assign-modal');
}
</script>
@endpush
