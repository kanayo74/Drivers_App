@extends('layouts.admin')
@section('page-title', 'Driver Training')

@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('training-assign-modal')">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Assign Training
</button>
@endsection

@section('content')

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total assignments</div>
        <div class="stat-value text-accent">{{ $assignments->total() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Upcoming</div>
        <div class="stat-value text-amber">
            {{ \App\Models\TrainingAssignment::where('status','upcoming')->count() }}
        </div>
        <span class="stat-badge badge-amber">Scheduled</span>
    </div>
    <div class="stat-card">
        <div class="stat-label">In progress</div>
        <div class="stat-value text-purple">
            {{ \App\Models\TrainingAssignment::where('status','in_progress')->count() }}
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value text-green">
            {{ \App\Models\TrainingAssignment::where('status','completed')->count() }}
        </div>
    </div>
</div>

{{-- TABLE --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">All training assignments</div>
            <div class="card-sub">{{ $assignments->total() }} records</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Training type</th>
                <th>Provider</th>
                <th>Date</th>
                <th>Duration</th>
                <th>Notes</th>
                <th>Status</th>
                <th>Assigned by</th>
                <th>Update status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $a)
            <tr>
                <td>
                    <div class="td-name">{{ $a->driver->name }}</div>
                    <div style="font-size:11px;color:var(--text3)">{{ $a->driver->employee_id }}</div>
                </td>
                <td class="td-name">{{ $a->training_type }}</td>
                <td class="text-muted">{{ $a->provider ?? '—' }}</td>
                <td>
                    <div style="font-size:13px">{{ $a->training_date->format('d M Y') }}</div>
                    <div style="font-size:11px;color:var(--text3)">
                        {{ $a->training_date->isPast() ? $a->training_date->diffForHumans() : 'In ' . $a->training_date->diffForHumans() }}
                    </div>
                </td>
                <td class="text-muted">{{ $a->duration_days }} day(s)</td>
                <td style="max-width:150px">
                    <div style="font-size:12px;color:var(--text2)">
                        {{ $a->notes ? Str::limit($a->notes, 50) : '—' }}
                    </div>
                </td>
                <td>
                    <span class="status-pill status-{{ match($a->status) {
                        'completed'   => 'approved',
                        'in_progress' => 'in_progress',
                        'cancelled'   => 'rejected',
                        default       => 'pending'
                    } }}">
                        {{ ucfirst(str_replace('_', ' ', $a->status)) }}
                    </span>
                </td>
                <td class="text-muted" style="font-size:12px">
                    {{ $a->assignedBy->name }}
                    <div style="font-size:10px;color:var(--text3)">
                        {{ $a->created_at->format('d M Y') }}
                    </div>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.training.status', $a) }}"
                        style="display:flex;gap:6px;align-items:center">
                        @csrf
                        <select name="status" class="form-select"
                            style="width:130px;padding:5px 8px;font-size:12px">
                            @foreach(['upcoming','in_progress','completed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $a->status === $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9">
                    <div style="padding:48px;text-align:center">
                        <div style="font-size:36px;margin-bottom:12px">📚</div>
                        <div style="font-size:14px;font-weight:500;margin-bottom:6px">No training assigned yet</div>
                        <div style="font-size:13px;color:var(--text3);margin-bottom:16px">
                            Assign training sessions to keep your drivers skilled and certified.
                        </div>
                        <button class="btn btn-primary" onclick="openModal('training-assign-modal')">
                            Assign first training
                        </button>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding:14px 20px;border-top:1px solid var(--border)">
        {{ $assignments->links() }}
    </div>
</div>

{{-- ASSIGN TRAINING MODAL --}}
<div class="modal-bg" id="training-assign-modal">
    <div class="modal" style="width:520px">
        <div class="modal-title">Assign driver training</div>
        <div class="modal-sub">
            Schedule a training session. The driver will be notified immediately.
        </div>

        <form method="POST" action="{{ route('admin.training.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">Driver <span style="color:var(--red)">*</span></label>
                <select name="driver_id" class="form-select" required>
                    <option value="">Select driver…</option>
                    @foreach($drivers as $d)
                    <option value="{{ $d->id }}">
                        {{ $d->name }}
                        @if($d->assignedVehicle) — {{ $d->assignedVehicle->plate_number }} @endif
                        [{{ ucfirst(str_replace('_', ' ', $d->driverProfile?->status ?? 'available')) }}]
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Training type <span style="color:var(--red)">*</span></label>
                <select name="training_type" class="form-select" required>
                    <option value="">Select type…</option>
                    <option>Defensive driving</option>
                    <option>Customer service</option>
                    <option>First aid & emergency response</option>
                    <option>Vehicle handling & maintenance</option>
                    <option>Road safety & traffic laws</option>
                    <option>Corporate etiquette</option>
                    <option>Other</option>
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Training date <span style="color:var(--red)">*</span></label>
                    <input type="date" name="training_date" class="form-input" required
                        min="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (days) <span style="color:var(--red)">*</span></label>
                    <input type="number" name="duration_days" class="form-input"
                        value="1" min="1" max="30" required>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Provider / Instructor</label>
                    <input type="text" name="provider" class="form-input"
                        placeholder="e.g. LASDRI, Nigerian Red Cross, Internal…">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes / Instructions</label>
                <textarea name="notes" class="form-textarea" rows="3"
                    placeholder="Additional details, venue, what to bring…"></textarea>
            </div>

            <div style="background:rgba(167,139,250,.08);border:1px solid rgba(167,139,250,.2);
                border-radius:8px;padding:12px 14px;margin-bottom:4px;font-size:12px;color:var(--purple)">
                📌 Assigning training will set the driver's status to <strong>Training</strong> and notify them via email.
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('training-assign-modal')">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Assign & Notify Driver
                </button>
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
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => openModal('training-assign-modal'));
@endif
</script>
@endpush
