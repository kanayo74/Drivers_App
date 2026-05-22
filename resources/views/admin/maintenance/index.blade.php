{{-- admin/maintenance/index.blade.php --}}
@extends('layouts.admin')
@section('page-title','Maintenance')
@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('maintenance-modal')">+ Log Service</button>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <div><div class="card-title">Maintenance schedule</div><div class="card-sub">Service history and upcoming dates per vehicle</div></div>
    </div>
    @php $overdue = $records->where('status','overdue'); @endphp
    @if($overdue->count())
    <div class="alert alert-red" style="margin:14px 20px 0">⚠️ {{ $overdue->count() }} vehicle(s) have overdue maintenance. Attend to them immediately.</div>
    @endif
    <table>
        <thead><tr><th>Vehicle</th><th>Service type</th><th>Last done</th><th>Next due</th><th>Provider</th><th>Cost</th><th>Status</th><th>Logged by</th></tr></thead>
        <tbody>
            @forelse($records as $rec)
            <tr>
                <td class="td-name">{{ $rec->vehicle->make }} {{ $rec->vehicle->model }}<div style="font-family:'DM Mono',monospace;font-size:11px;color:var(--text3)">{{ $rec->vehicle->plate_number }}</div></td>
                <td>{{ $rec->service_type }}</td>
                <td class="text-muted" style="font-size:12px">{{ $rec->service_date->format('d M Y') }}</td>
                <td class="{{ $rec->isOverdue() ? 'text-red' : ($rec->next_service_date?->diffInDays(now()) < 14 ? 'text-amber' : 'text-muted') }}" style="font-size:12px">{{ $rec->next_service_date?->format('d M Y') ?? '—' }}</td>
                <td class="text-muted">{{ $rec->provider ?? '—' }}</td>
                <td class="text-muted">{{ $rec->cost ? '₦'.number_format($rec->cost) : '—' }}</td>
                <td><span class="status-pill status-{{ match($rec->status){'completed'=>'approved','overdue'=>'rejected',default=>'pending'} }}">{{ ucfirst($rec->status) }}</span></td>
                <td class="text-muted" style="font-size:12px">{{ $rec->loggedBy->name }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty-state">No maintenance records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 20px">{{ $records->links() }}</div>
</div>

<div class="modal-bg" id="maintenance-modal">
    <div class="modal">
        <div class="modal-title">Log maintenance record</div>
        <form method="POST" action="{{ route('admin.maintenance.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Vehicle</label>
                <select name="vehicle_id" class="form-select" required>
                    <option value="">Select vehicle…</option>
                    @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->make }} {{ $v->model }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Service type</label>
                <select name="service_type" class="form-select" required>
                    <option>Oil change</option><option>Full service</option><option>Brake check</option>
                    <option>Tyre rotation</option><option>Battery replacement</option>
                    <option>Transmission service</option><option>Other</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Service date</label><input type="date" name="service_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Next service date</label><input type="date" name="next_service_date" class="form-input"></div>
                <div class="form-group"><label class="form-label">Mileage at service (km)</label><input type="number" name="mileage_at_service" class="form-input"></div>
                <div class="form-group"><label class="form-label">Cost (₦)</label><input type="number" name="cost" class="form-input" step="0.01"></div>
                <div class="form-group" style="grid-column:1/-1"><label class="form-label">Provider / Workshop</label><input type="text" name="provider" class="form-input" placeholder="e.g. Toyota Service Centre"></div>
                <div class="form-group" style="grid-column:1/-1"><label class="form-label">Notes</label><textarea name="notes" class="form-textarea" placeholder="What was done or observed…"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('maintenance-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save record</button>
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
</script>
@endpush


{{-- ════════════════════════════════════════
  admin/training/index.blade.php  (append below in your project as a separate file)
════════════════════════════════════════ --}}
@extends('layouts.admin')
@section('page-title','Driver Training')
@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('training-assign-modal')">+ Assign Training</button>
@endsection
@section('content')
<div class="card">
    <div class="card-header"><div class="card-title">All training assignments</div><div class="card-sub">{{ $assignments->total() }} records</div></div>
    <table>
        <thead><tr><th>Driver</th><th>Training type</th><th>Provider</th><th>Date</th><th>Duration</th><th>Status</th><th>Assigned by</th><th>Update</th></tr></thead>
        <tbody>
            @forelse($assignments as $a)
            <tr>
                <td class="td-name">{{ $a->driver->name }}</td>
                <td>{{ $a->training_type }}</td>
                <td class="text-muted">{{ $a->provider ?? '—' }}</td>
                <td class="text-muted" style="font-size:12px">{{ $a->training_date->format('d M Y') }} ({{ $a->duration_days }}d)</td>
                <td class="text-muted">{{ $a->duration_days }} day(s)</td>
                <td><span class="status-pill status-{{ match($a->status){'completed'=>'approved','in_progress'=>'in_progress','cancelled'=>'rejected',default=>'pending'} }}">{{ ucfirst($a->status) }}</span></td>
                <td class="text-muted" style="font-size:12px">{{ $a->assignedBy->name }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.training.status', $a) }}" style="display:flex;gap:6px">
                        @csrf
                        <select name="status" class="form-select" style="width:130px;padding:5px 8px;font-size:12px">
                            @foreach(['upcoming','in_progress','completed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $a->status===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty-state">No training assignments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 20px">{{ $assignments->links() }}</div>
</div>

<div class="modal-bg" id="training-assign-modal">
    <div class="modal">
        <div class="modal-title">Assign training</div>
        <form method="POST" action="{{ route('admin.training.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Driver</label>
                <select name="driver_id" class="form-select" required>
                    <option value="">Select driver…</option>
                    @foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Training type</label>
                <select name="training_type" class="form-select" required>
                    <option>Defensive driving</option><option>Customer service</option>
                    <option>First aid</option><option>Vehicle handling</option>
                    <option>Road safety</option><option>Other</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Provider</label><input type="text" name="provider" class="form-input" placeholder="e.g. LASDRI, Internal…"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group"><label class="form-label">Date</label><input type="date" name="training_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Duration (days)</label><input type="number" name="duration_days" class="form-input" value="1" min="1"></div>
            </div>
            <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-textarea"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('training-assign-modal')">Cancel</button>
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
</script>
@endpush
