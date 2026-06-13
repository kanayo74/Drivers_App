{{-- admin/maintenance/index.blade.php --}}
@extends('layouts.admin')
@section('page-title','Maintenance')
@section('topbar-actions')
	<button class="btn btn-primary" onclick="openModal('maintenance-modal')">+ Log Service</button>
@endsection

@section('content')
	<div class="card" style="margin-bottom:10px">
		@forelse($vehicles as $vehicle)
			<div class="card" style="margin-bottom:10px">
				<div class="card-header">
					<div>
						<div class="card-title">{{ $vehicle->plate_number }} — {{ $vehicle->make }} {{ $vehicle->model }}</div>
						<div class="card-sub">Last maintenance: {{ optional($vehicle->last_maintenance_at)->diffForHumans() ?? 'Never' }}</div>
					</div>
				</div>
				<div style="padding:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
					@if(in_array('role:admin', request()->route()->middleware()))
						<form method="POST" action="{{ route('admin.maintenance.done', $vehicle) }}">
							@csrf
							<button class="btn btn-primary" type="submit">Mark monthly maintenance done</button>
						</form>
					@endif
					
					<button class="btn btn-ghost" onclick="toggleReport('report-{{ $vehicle->id }}')">Report issue</button>
					
					<div id="report-{{ $vehicle->id }}" class="report-panel" style="display:none;margin-left:12px">
						<form method="POST" action="{{ route('admin.maintenance.report', $vehicle) }}">
							@csrf
							<input type="text" name="type" placeholder="e.g. oil, tires" class="form-input" style="margin-bottom:6px">
							<textarea name="issue" required class="form-textarea" rows="2" placeholder="Describe the problem..."></textarea>
							<div style="margin-top:6px">
								<button class="btn btn-red" type="submit">Send to admin</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		@empty
			<div class="empty-state">No vehicles found.</div>
		@endforelse
	</div>
	
	<div class="card">
		<div class="card-header">
			<div>
				<div class="card-title">Maintenance schedule</div>
				<div class="card-sub">Service history and upcoming dates per vehicle</div>
			</div>
		</div>
		@php $overdue = isset($records) ? $records->where('status','overdue') : collect(); @endphp
		@if($overdue->count())
			<div class="alert alert-red" style="margin:14px 20px 0">⚠️ {{ $overdue->count() }} vehicle(s) have overdue maintenance. Attend to them immediately.</div>
		@endif
		<table class="table">
			<thead>
			<tr>
				<th>Vehicle</th>
				<th>Service type</th>
				<th>Last done</th>
				<th>Next due</th>
				<th>Provider</th>
				<th>Cost</th>
				<th>Status</th>
				<th>Logged by</th>
			</tr>
			</thead>
			<tbody>
			@forelse($records ?? [] as $rec)
				<tr>
					<td class="td-name">{{ $rec->vehicle->make ?? '' }} {{ $rec->vehicle->model ?? '' }}
						<div style="font-family:'DM Mono',monospace;font-size:11px;color:var(--text3)">{{ $rec->vehicle->plate_number ?? '' }}</div>
					</td>
					<td>{{ $rec->service_type ?? '' }}</td>
					<td class="text-muted" style="font-size:12px">{{ isset($rec->service_date) ? $rec->service_date->format('d M Y') : '—' }}</td>
					<td class="{{ isset($rec->next_service_date) ? ($rec->next_service_date->isPast() ? 'text-red' : ($rec->next_service_date->diffInDays(now()) < 14 ? 'text-amber' : 'text-muted')) : 'text-muted' }}" style="font-size:12px">{{ $rec->next_service_date?->format('d M Y') ?? '—' }}</td>
					<td class="text-muted">{{ $rec->provider ?? '—' }}</td>
					<td class="text-muted">{{ isset($rec->cost) ? '₦'.number_format($rec->cost) : '—' }}</td>
					<td><span class="status-pill status-{{ match($rec->status ?? 'pending'){'completed'=>'approved','overdue'=>'rejected',default=>'pending'} }}">{{ ucfirst($rec->status ?? 'pending') }}</span></td>
					<td class="text-muted" style="font-size:12px">{{ $rec->loggedBy->name ?? '—' }}</td>
				</tr>
			@empty
				<tr>
					<td colspan="8" class="empty-state">No maintenance records yet.</td>
				</tr>
			@endforelse
			</tbody>
		</table>
		@if(isset($records) && method_exists($records, 'links'))
			<div style="padding:14px 20px">{{ $records->links() }}</div>
		@endif
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
						@foreach($vehicles ?? [] as $v)
							<option value="{{ $v->id }}">{{ $v->plate_number }} — {{ $v->make }} {{ $v->model }}</option>
						@endforeach
					</select>
				</div>
				<div class="form-group">
					<label class="form-label">Service type</label>
					<select name="service_type" class="form-select" required>
						<option>Oil change</option>
						<option>Full service</option>
						<option>Brake check</option>
						<option>Tyre rotation</option>
						<option>Battery replacement</option>
						<option>Transmission service</option>
						<option>Other</option>
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
		function openModal(id) {
			document.getElementById(id).classList.add('open');
		}
		
		function closeModal(id) {
			document.getElementById(id).classList.remove('open');
		}
		
		function toggleReport(id) {
			const panel = document.getElementById(id);
			if (panel) {
				panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
			}
		}
		
		document.querySelectorAll('.modal-bg').forEach(m => {
			m.addEventListener('click', e => {
				if (e.target === m) m.classList.remove('open');
			});
		});
	</script>
@endpush
