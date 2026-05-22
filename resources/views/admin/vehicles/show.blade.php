@extends('layouts.admin')
@section('page-title', $vehicle->make . ' ' . $vehicle->model . ' — ' . $vehicle->plate_number)

@section('topbar-actions')
<a href="{{ route('admin.vehicles.index') }}" class="btn btn-ghost">← Back to Fleet</a>
<button class="btn btn-primary" onclick="openModal('maintenance-modal')">+ Log Service</button>
@endsection

@section('content')

<div class="two-col" style="align-items:start">

    {{-- LEFT COLUMN --}}
    <div>

        {{-- Vehicle overview card --}}
        <div class="card">
            <div style="padding:24px">
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
                    <div style="font-size:52px">
                        {{ $vehicle->type === 'staff_bus' ? '🚌' : ($vehicle->type === 'executive_car' ? '🏎️' : '🚗') }}
                    </div>
                    <div style="flex:1">
                        <div style="font-size:20px;font-weight:600">
                            {{ $vehicle->make }} {{ $vehicle->model }}
                        </div>
                        <div style="font-family:'DM Mono',monospace;font-size:14px;color:var(--text3);margin-top:2px">
                            {{ $vehicle->plate_number }}
                        </div>
                        <div style="font-size:12px;color:var(--text3);margin-top:4px">
                            {{ str_replace('_',' ',ucfirst($vehicle->type)) }}
                            · {{ $vehicle->year }}
                            @if($vehicle->color) · {{ $vehicle->color }} @endif
                        </div>
                    </div>
                    <span class="status-pill status-{{ match($vehicle->status) {
                        'active'     => 'approved',
                        'in_service' => 'training',
                        'inactive'   => 'pending',
                        default      => 'rejected'
                    } }}">
                        {{ ucfirst(str_replace('_',' ',$vehicle->status)) }}
                    </span>
                </div>

                {{-- Vehicle detail grid --}}
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px">
                    <div style="padding:10px 12px;background:var(--bg3);border-radius:8px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace;margin-bottom:4px">Engine</div>
                        <div style="font-size:14px;font-weight:600">{{ $vehicle->engine_size }}L</div>
                    </div>
                    <div style="padding:10px 12px;background:var(--bg3);border-radius:8px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace;margin-bottom:4px">Tank</div>
                        <div style="font-size:14px;font-weight:600">{{ $vehicle->tank_capacity }}L</div>
                    </div>
                    <div style="padding:10px 12px;background:var(--bg3);border-radius:8px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace;margin-bottom:4px">Consumption</div>
                        <div style="font-size:14px;font-weight:600">{{ $vehicle->fuel_consumption_per_hour }}L/hr</div>
                    </div>
                    <div style="padding:10px 12px;background:var(--bg3);border-radius:8px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace;margin-bottom:4px">Mileage</div>
                        <div style="font-size:14px;font-weight:600">{{ number_format($vehicle->current_mileage) }} km</div>
                    </div>
                    <div style="padding:10px 12px;background:var(--bg3);border-radius:8px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace;margin-bottom:4px">Last Service</div>
                        <div style="font-size:13px;font-weight:500">{{ $vehicle->last_service_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div style="padding:10px 12px;background:var(--bg3);border-radius:8px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace;margin-bottom:4px">Next Service</div>
                        <div style="font-size:13px;font-weight:500;color:{{ $vehicle->isServiceDue() ? 'var(--red)' : 'var(--text)' }}">
                            {{ $vehicle->next_service_date?->format('d M Y') ?? '—' }}
                            @if($vehicle->isServiceDue()) <span style="font-size:11px">⚠️ Overdue</span> @endif
                        </div>
                    </div>
                </div>

                {{-- Fuel section --}}
                <div style="background:var(--bg3);border-radius:10px;padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <span style="font-size:13px;font-weight:500">⛽ Fuel level</span>
                        <span style="font-weight:700;font-size:16px;color:{{ $vehicle->fuel_percent <= 25 ? 'var(--red)' : ($vehicle->fuel_percent <= 50 ? 'var(--amber)' : 'var(--green)') }}">
                            {{ $vehicle->fuel_percent }}%
                            <span style="font-size:12px;font-weight:400;color:var(--text3)">
                                ({{ $vehicle->current_fuel_level }}L)
                            </span>
                        </span>
                    </div>
                    <div class="fuel-bar-lg">
                        <div class="fuel-bar-fill" style="width:{{ $vehicle->fuel_percent }}%;
                            background:{{ $vehicle->fuel_percent <= 25 ? 'var(--red)' : ($vehicle->fuel_percent <= 50 ? 'var(--amber)' : 'var(--green)') }}">
                        </div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:var(--text3)">
                        <span>~{{ round($vehicle->estimated_hours_remaining, 1) }} hours remaining</span>
                        <span>Est. empty: {{ $vehicle->estimated_empty_at?->format('D d M, H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Assigned driver --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Assigned driver</div>
                <button class="btn btn-ghost btn-sm" onclick="openModal('assign-driver-modal')">
                    Change driver
                </button>
            </div>
            @if($vehicle->assignedDriver)
            <div style="padding:16px 20px;display:flex;align-items:center;gap:14px">
                <div class="driver-avatar"
                    style="width:48px;height:48px;font-size:16px;background:rgba(79,124,255,.15);color:var(--accent)">
                    {{ strtoupper(substr($vehicle->assignedDriver->name, 0, 2)) }}
                </div>
                <div style="flex:1">
                    <div class="td-name" style="font-size:15px">{{ $vehicle->assignedDriver->name }}</div>
                    <div class="text-muted" style="font-size:12px">
                        {{ $vehicle->assignedDriver->employee_id }}
                        · {{ $vehicle->assignedDriver->phone ?? 'No phone' }}
                    </div>
                    <div style="font-size:12px;color:var(--amber);margin-top:2px">
                        ★ {{ round($vehicle->assignedDriver->ratings()->avg('rating') ?? 0, 1) }} avg rating
                    </div>
                </div>
                <a href="{{ route('admin.drivers.show', $vehicle->assignedDriver) }}"
                    class="btn btn-ghost btn-sm">
                    View profile
                </a>
            </div>
            @else
            <div style="padding:24px;text-align:center">
                <div style="font-size:13px;color:var(--text3);margin-bottom:12px">No driver assigned</div>
                <button class="btn btn-primary btn-sm" onclick="openModal('assign-driver-modal')">
                    Assign a driver
                </button>
            </div>
            @endif
        </div>

        {{-- Maintenance history --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Maintenance history</div>
                    <div class="card-sub">{{ $vehicle->maintenanceRecords->count() }} records</div>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="openModal('maintenance-modal')">
                    + Log service
                </button>
            </div>
            @forelse($vehicle->maintenanceRecords->sortByDesc('service_date') as $m)
            <div style="padding:13px 20px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
                    <div style="flex:1">
                        <div class="td-name">{{ $m->service_type }}</div>
                        <div class="trip-meta">
                            {{ $m->service_date->format('d M Y') }}
                            @if($m->provider) · {{ $m->provider }} @endif
                            @if($m->cost) · ₦{{ number_format($m->cost) }} @endif
                            @if($m->mileage_at_service) · {{ number_format($m->mileage_at_service) }} km @endif
                        </div>
                        @if($m->next_service_date)
                        <div class="trip-meta"
                            style="color:{{ $m->isOverdue() ? 'var(--red)' : 'var(--text3)' }}">
                            Next: {{ $m->next_service_date->format('d M Y') }}
                            {{ $m->isOverdue() ? '⚠️ Overdue' : '' }}
                        </div>
                        @endif
                        @if($m->notes)
                        <div style="font-size:12px;color:var(--text2);margin-top:3px">{{ $m->notes }}</div>
                        @endif
                    </div>
                    <span class="status-pill status-{{ match($m->status) {
                        'completed' => 'approved',
                        'overdue'   => 'rejected',
                        default     => 'pending'
                    } }}">{{ ucfirst($m->status) }}</span>
                </div>
            </div>
            @empty
            <div class="empty-state">No maintenance records yet.</div>
            @endforelse
        </div>
    </div>

    {{-- RIGHT COLUMN --}}
    <div>

        {{-- Fuel log --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Fuel log</div>
                    <div class="card-sub">Last 10 refuels</div>
                </div>
            </div>
            @forelse($vehicle->fuelLogs->sortByDesc('created_at')->take(10) as $log)
            <div style="padding:12px 20px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div class="td-name">
                            {{ ucfirst($log->fill_type) }} refuel
                            <span style="color:var(--green);font-weight:400;font-size:12px">
                                +{{ $log->litres_added }}L
                            </span>
                        </div>
                        <div class="trip-meta">
                            Before: {{ $log->level_before }}L
                            → After: {{ $log->level_after }}L
                            @if($log->cost) · ₦{{ number_format($log->cost) }} @endif
                        </div>
                        @if($log->estimated_empty_at)
                        <div class="trip-meta">
                            Est. empty after fill: {{ $log->estimated_empty_at->format('D d M, H:i') }}
                        </div>
                        @endif
                    </div>
                    <div class="text-muted" style="font-size:11px;text-align:right">
                        {{ $log->created_at->diffForHumans() }}<br>
                        <span style="font-family:'DM Mono',monospace">{{ $log->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">No fuel logs yet.</div>
            @endforelse
        </div>

        {{-- Recent trips --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Recent trips</div>
                    <div class="card-sub">{{ $vehicle->trips->count() }} total</div>
                </div>
                <a href="{{ route('admin.trips.index') }}?vehicle_id={{ $vehicle->id }}"
                    class="btn btn-ghost btn-sm">View all</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Trip</th>
                        <th>Driver</th>
                        <th>Passenger</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicle->trips->sortByDesc('scheduled_at')->take(10) as $trip)
                    <tr>
                        <td>
                            <a href="{{ route('admin.trips.show', $trip) }}" class="trip-code">
                                #{{ $trip->trip_code }}
                            </a>
                        </td>
                        <td class="td-name">{{ $trip->driver->name }}</td>
                        <td class="text-muted">{{ $trip->passenger->name }}</td>
                        <td class="text-muted" style="font-size:12px">
                            {{ $trip->scheduled_at->format('d M Y') }}
                        </td>
                        <td>
                            <span class="status-pill status-{{ $trip->status }}">
                                {{ ucfirst(str_replace('_',' ',$trip->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">No trips yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Fuel chart data summary --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Fuel snapshot history</div>
                <div class="card-sub">Last 24 hours</div>
            </div>
            <div style="padding:16px 20px">
                @forelse($fuelHistory->take(8) as $snap)
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                    <div style="font-size:11px;color:var(--text3);width:90px;font-family:'DM Mono',monospace;flex-shrink:0">
                        {{ $snap->recorded_at->format('d M H:i') }}
                    </div>
                    <div style="flex:1">
                        <div class="fuel-bar-lg">
                            <div class="fuel-bar-fill"
                                style="width:{{ $snap->level_percent }}%;
                                background:{{ $snap->level_percent <= 25 ? 'var(--red)' : ($snap->level_percent <= 50 ? 'var(--amber)' : 'var(--green)') }}">
                            </div>
                        </div>
                    </div>
                    <div style="font-size:12px;font-weight:600;width:36px;text-align:right;
                        color:{{ $snap->level_percent <= 25 ? 'var(--red)' : ($snap->level_percent <= 50 ? 'var(--amber)' : 'var(--green)') }}">
                        {{ $snap->level_percent }}%
                    </div>
                </div>
                @empty
                <div class="empty-state" style="padding:16px">No snapshot data yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- LOG MAINTENANCE MODAL --}}
<div class="modal-bg" id="maintenance-modal">
    <div class="modal" style="width:520px">
        <div class="modal-title">Log maintenance record</div>
        <div class="modal-sub">For: {{ $vehicle->make }} {{ $vehicle->model }} — {{ $vehicle->plate_number }}</div>
        <form method="POST" action="{{ route('admin.maintenance.store') }}">
            @csrf
            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
            <div class="form-group">
                <label class="form-label">Service type <span style="color:var(--red)">*</span></label>
                <select name="service_type" class="form-select" required>
                    <option value="">Select type…</option>
                    <option>Oil change</option>
                    <option>Full service</option>
                    <option>Brake check & replacement</option>
                    <option>Tyre rotation & replacement</option>
                    <option>Battery replacement</option>
                    <option>Transmission service</option>
                    <option>Air filter replacement</option>
                    <option>Coolant flush</option>
                    <option>Other</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Service date <span style="color:var(--red)">*</span></label>
                    <input type="date" name="service_date" class="form-input" required
                        value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Next service date</label>
                    <input type="date" name="next_service_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Mileage at service (km)</label>
                    <input type="number" name="mileage_at_service" class="form-input"
                        value="{{ $vehicle->current_mileage }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Cost (₦)</label>
                    <input type="number" name="cost" class="form-input" step="0.01" placeholder="0.00">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Provider / Workshop</label>
                    <input type="text" name="provider" class="form-input"
                        placeholder="e.g. Toyota Service Centre, Local mechanic…">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-textarea" rows="3"
                        placeholder="What was done, parts replaced, observations…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('maintenance-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save record</button>
            </div>
        </form>
    </div>
</div>

{{-- ASSIGN DRIVER MODAL --}}
<div class="modal-bg" id="assign-driver-modal">
    <div class="modal">
        <div class="modal-title">Assign driver</div>
        <div class="modal-sub">{{ $vehicle->plate_number }} — {{ $vehicle->make }} {{ $vehicle->model }}</div>
        <form method="POST" action="{{ route('admin.vehicles.assign-driver', $vehicle) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Select driver</label>
                <select name="driver_id" class="form-select" required>
                    <option value="">Choose driver…</option>
                    @foreach(\App\Models\User::where('role','driver')->where('is_active',true)->get() as $d)
                    <option value="{{ $d->id }}"
                        {{ $vehicle->assigned_driver_id === $d->id ? 'selected' : '' }}>
                        {{ $d->name }}
                        @if($d->assignedVehicle && $d->assignedVehicle->id !== $vehicle->id)
                            (has {{ $d->assignedVehicle->plate_number }})
                        @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('assign-driver-modal')">Cancel</button>
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
document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
</script>
@endpush
