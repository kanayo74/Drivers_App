@extends('layouts.driver')
@section('page-title', 'My Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Trips this month</div>
        <div class="stat-value text-accent">{{ $stats['trips_this_month'] }}</div>
        <div class="stat-sub">{{ $stats['total_trips'] }} all time</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">My rating</div>
        <div class="stat-value text-amber">
            {{ $stats['avg_rating'] > 0 ? $stats['avg_rating'] . ' ★' : 'N/A' }}
        </div>
        <div class="stat-sub">From passenger reviews</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Outstanding pay</div>
        <div class="stat-value text-teal">₦{{ number_format($stats['outstanding_pay']) }}</div>
        <div class="stat-sub">Paid on weekends</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">My status</div>
        @php
            $s = auth()->user()->driverProfile?->status ?? 'available';
            $statusClass = match($s) {
                'available'  => 'text-green',
                'on_trip'    => 'text-teal',
                'training'   => 'text-purple',
                'suspended'  => 'text-red',
                default      => 'text-amber',
            };
        @endphp
        <div class="stat-value {{ $statusClass }}" style="font-size:18px;text-transform:capitalize">
            {{ str_replace('_', ' ', $s) }}
        </div>
        @if($vehicle)
        <div class="stat-sub">{{ $vehicle->plate_number }}</div>
        @endif
    </div>
</div>

{{-- ACTIVE TRIP BANNER --}}
@if($activeTrip)
<div class="card" style="border-color:rgba(45,212,191,.4);margin-bottom:20px">
    <div style="padding:16px 20px;background:rgba(45,212,191,.05);border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-size:14px;font-weight:600;color:var(--teal)">
                    🚗 Active Trip — #{{ $activeTrip->trip_code }}
                </div>
                <div class="trip-meta" style="margin-top:4px">
                    Passenger: <strong style="color:var(--text)">{{ $activeTrip->passenger->name }}</strong>
                    · {{ $activeTrip->vehicle->plate_number }}
                    · Started {{ $activeTrip->started_at?->diffForHumans() }}
                </div>
                @if($activeTrip->destination)
                <div class="trip-meta">📍 Destination: {{ $activeTrip->destination }}</div>
                @endif
                <div class="trip-meta">Reason: {{ Str::limit($activeTrip->reason, 60) }}</div>
            </div>
            <form method="POST" action="{{ route('driver.trips.complete', $activeTrip) }}"
                onsubmit="return confirm('Mark this trip as completed?')">
                @csrf
                <button class="btn btn-green">
                    ✓ Mark Completed
                </button>
            </form>
        </div>
    </div>

    {{-- Route stop logging (staff bus only) --}}
    @if($activeTrip->vehicle->type === 'staff_bus')
    <div style="padding:16px 20px">
        <div style="font-size:12px;font-weight:500;color:var(--text2);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.8px;font-family:'DM Mono',monospace">
            Log pickup / drop-off stops
        </div>
        <form method="POST" action="{{ route('driver.trips.log-location', $activeTrip) }}"
            style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            @csrf
            <input type="text" name="location_name" class="form-input"
                placeholder="Stop name e.g. Lekki Phase 1 Gate, CMS Bus Stop"
                required style="flex:1;min-width:200px">
            <select name="direction" class="form-select" style="width:160px">
                <option value="morning">🌅 Morning (pickup)</option>
                <option value="evening">🌆 Evening (drop-off)</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Log Stop</button>
        </form>

        @if($activeTrip->locations->count())
        <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
            @foreach($activeTrip->locations->groupBy('direction') as $dir => $stops)
            <div>
                <div style="font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;font-family:'DM Mono',monospace">
                    {{ ucfirst($dir) }} route — {{ $stops->count() }} stops
                </div>
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
    @endif
</div>
@endif

<div class="two-col">

    {{-- LEFT: PENDING TRIP REQUESTS --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Trip requests</div>
                    <div class="card-sub">{{ $pendingRequests->count() }} approved — ready to start</div>
                </div>
                @if($pendingRequests->count())
                <span class="stat-badge badge-green">{{ $pendingRequests->count() }} waiting</span>
                @endif
            </div>

            @forelse($pendingRequests as $trip)
            <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                            <span class="trip-code">#{{ $trip->trip_code }}</span>
                            <span class="status-pill status-approved">Approved</span>
                        </div>
                        <div class="td-name" style="margin-bottom:3px">{{ $trip->passenger->name }}</div>
                        <div class="trip-meta">
                            🚗 {{ $trip->vehicle->plate_number }}
                            · {{ str_replace('_',' ',ucfirst($trip->vehicle->type)) }}
                        </div>
                        @if($trip->destination)
                        <div class="trip-meta">📍 {{ $trip->destination }}</div>
                        @endif
                        <div class="trip-meta">
                            🕐 {{ $trip->scheduled_at->format('D, d M Y H:i') }}
                        </div>
                        <div style="margin-top:6px;padding:8px 10px;background:var(--bg3);border-radius:6px;font-size:12px;color:var(--text2);line-height:1.5">
                            {{ Str::limit($trip->reason, 80) }}
                        </div>
                    </div>
                    <div style="flex-shrink:0">
                        @if(!$activeTrip)
                        <form method="POST" action="{{ route('driver.trips.start', $trip) }}"
                            onsubmit="return confirm('Start trip for {{ addslashes($trip->passenger->name) }}?')">
                            @csrf
                            <button class="btn btn-primary">Start Trip →</button>
                        </form>
                        @else
                        <button class="btn btn-ghost btn-sm" disabled style="opacity:.4">
                            Finish active trip first
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:36px;text-align:center">
                <div style="font-size:32px;margin-bottom:10px">✅</div>
                <div style="font-size:13px;font-weight:500;margin-bottom:4px">No pending trips</div>
                <div style="font-size:12px;color:var(--text3)">You're all clear. New requests will appear here.</div>
            </div>
            @endforelse
        </div>

        {{-- TODAY'S SCHEDULE --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Today's schedule</div>
                    <div class="card-sub">{{ $todayTrips->count() }} trips on {{ now()->format('d M Y') }}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr><th>Code</th><th>Passenger</th><th>Time</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($todayTrips as $trip)
                    <tr>
                        <td class="trip-code">#{{ $trip->trip_code }}</td>
                        <td class="td-name">{{ $trip->passenger->name }}</td>
                        <td class="text-muted" style="font-size:12px">{{ $trip->scheduled_at->format('H:i') }}</td>
                        <td>
                            <span class="status-pill status-{{ $trip->status }}">
                                {{ ucfirst(str_replace('_',' ',$trip->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty-state">No trips scheduled today.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- RIGHT: MY VEHICLE + FUEL --}}
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">My vehicle</div>
                @if($vehicle)
                <span class="status-pill status-{{ match($vehicle->status){'active'=>'approved','in_service'=>'training',default=>'rejected'} }}">
                    {{ ucfirst(str_replace('_',' ',$vehicle->status)) }}
                </span>
                @endif
            </div>

            @if($vehicle)
            <div style="padding:20px">
                {{-- Vehicle header --}}
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;padding:14px;background:var(--bg3);border-radius:8px">
                    <div style="font-size:38px">
                        {{ $vehicle->type === 'staff_bus' ? '🚌' : ($vehicle->type === 'executive_car' ? '🏎️' : '🚗') }}
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:600">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                        <div style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text3)">{{ $vehicle->plate_number }}</div>
                        <div style="font-size:11px;color:var(--text3);margin-top:2px">
                            {{ str_replace('_',' ',ucfirst($vehicle->type)) }} · {{ $vehicle->year }}
                        </div>
                    </div>
                </div>

                {{-- Fuel level --}}
                <div style="margin-bottom:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span style="font-size:12px;font-weight:500;color:var(--text2)">⛽ Fuel level</span>
                        <span style="font-weight:700;font-size:15px;color:{{ $vehicle->fuel_percent <= 25 ? 'var(--red)' : ($vehicle->fuel_percent <= 50 ? 'var(--amber)' : 'var(--green)') }}">
                            {{ $vehicle->fuel_percent }}%
                            <span style="font-size:12px;font-weight:400;color:var(--text3)">({{ $vehicle->current_fuel_level }}L / {{ $vehicle->tank_capacity }}L)</span>
                        </span>
                    </div>

                    <div class="fuel-bar-lg">
                        <div class="fuel-bar-fill" style="
                            width:{{ $vehicle->fuel_percent }}%;
                            background:{{ $vehicle->fuel_percent <= 25 ? 'var(--red)' : ($vehicle->fuel_percent <= 50 ? 'var(--amber)' : 'var(--green)') }}">
                        </div>
                    </div>

                    @if($vehicle->estimated_empty_at)
                    <div style="margin-top:6px;font-size:12px;
                        color:{{ $vehicle->estimated_hours_remaining < 4 ? 'var(--red)' : 'var(--text3)' }}">
                        {{ $vehicle->estimated_hours_remaining < 4 ? '⚠️' : '🕐' }}
                        Est. empty: {{ $vehicle->estimated_empty_at->format('D d M, H:i') }}
                        ({{ round($vehicle->estimated_hours_remaining, 1) }} hrs left)
                    </div>
                    @endif

                    @if($vehicle->isCriticalFuel())
                    <div class="alert alert-red" style="margin-top:10px;margin-bottom:0">
                        ⚠️ Fuel is critically low. Request a refuel immediately.
                    </div>
                    @endif
                </div>

                {{-- Fuel actions --}}
                <div style="border-top:1px solid var(--border);padding-top:14px">
                    <div style="font-size:12px;font-weight:500;color:var(--text2);margin-bottom:10px">
                        Fuel actions
                    </div>

                    {{-- Request fuel --}}
                    @php
                        $hasPendingRequest = \App\Models\FuelRequest::where('driver_id', auth()->id())
                            ->where('vehicle_id', $vehicle->id)
                            ->whereIn('status',['pending','acknowledged'])
                            ->exists();
                    @endphp

                    @if($hasPendingRequest)
                    <div class="alert alert-amber" style="margin-bottom:10px">
                        ⏳ Fuel request submitted — waiting for admin acknowledgement.
                    </div>
                    @else
                    <form method="POST" action="{{ route('driver.fuel.request') }}" style="margin-bottom:10px">
                        @csrf
                        <div class="form-group" style="margin-bottom:8px">
                            <textarea name="notes" class="form-textarea" rows="2"
                                placeholder="Optional note e.g. travelling to Abuja tomorrow, need full tank…"
                                style="font-size:12px"></textarea>
                        </div>
                        <button type="submit" class="btn btn-amber" style="width:100%;justify-content:center">
                            ⛽ Request Fuel
                        </button>
                    </form>
                    @endif

                    {{-- Mark fuelled --}}
                    <div style="font-size:11px;color:var(--text3);margin-bottom:8px">
                        After refuelling, tap below to update the system:
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <form method="POST" action="{{ route('driver.fuel.mark-fuelled', $vehicle) }}"
                            onsubmit="return confirm('Mark tank as FULL?')">
                            @csrf
                            <input type="hidden" name="fill_type" value="full">
                            <button type="submit" class="btn btn-green" style="width:100%;justify-content:center">
                                🟢 Mark Full
                            </button>
                        </form>
                        <form method="POST" action="{{ route('driver.fuel.mark-fuelled', $vehicle) }}"
                            onsubmit="return confirm('Mark tank as HALF?')">
                            @csrf
                            <input type="hidden" name="fill_type" value="half">
                            <button type="submit" class="btn btn-ghost" style="width:100%;justify-content:center">
                                🟡 Mark Half
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Vehicle info strip --}}
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);
                    display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div style="padding:8px 10px;background:var(--bg3);border-radius:6px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;font-family:'DM Mono',monospace">Engine</div>
                        <div style="font-size:13px;font-weight:500;margin-top:2px">{{ $vehicle->engine_size }}L</div>
                    </div>
                    <div style="padding:8px 10px;background:var(--bg3);border-radius:6px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;font-family:'DM Mono',monospace">Consumption</div>
                        <div style="font-size:13px;font-weight:500;margin-top:2px">{{ $vehicle->fuel_consumption_per_hour }} L/hr</div>
                    </div>
                    <div style="padding:8px 10px;background:var(--bg3);border-radius:6px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;font-family:'DM Mono',monospace">Last service</div>
                        <div style="font-size:13px;font-weight:500;margin-top:2px">{{ $vehicle->last_service_date?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div style="padding:8px 10px;background:var(--bg3);border-radius:6px">
                        <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;font-family:'DM Mono',monospace">Next service</div>
                        <div style="font-size:13px;font-weight:500;margin-top:2px;color:{{ $vehicle->isServiceDue() ? 'var(--red)' : 'var(--text)' }}">
                            {{ $vehicle->next_service_date?->format('d M Y') ?? '—' }}
                            {{ $vehicle->isServiceDue() ? '⚠️' : '' }}
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div style="padding:36px;text-align:center">
                <div style="font-size:36px;margin-bottom:10px">🚗</div>
                <div style="font-size:13px;font-weight:500;margin-bottom:4px">No vehicle assigned</div>
                <div style="font-size:12px;color:var(--text3)">Contact your admin to get a vehicle assigned.</div>
            </div>
            @endif
        </div>

        {{-- TRAINING --}}
        @php
            $upcomingTraining = auth()->user()->trainingAssignments()
                ->whereIn('status',['upcoming','in_progress'])
                ->orderBy('training_date')
                ->first();
        @endphp
        @if($upcomingTraining)
        <div class="card" style="border-color:rgba(167,139,250,.3)">
            <div style="padding:16px 20px;background:rgba(167,139,250,.05)">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="font-size:24px">📚</div>
                    <div style="flex:1">
                        <div style="font-size:13px;font-weight:600;color:var(--purple)">
                            Training assigned
                        </div>
                        <div style="font-size:13px;color:var(--text)">{{ $upcomingTraining->training_type }}</div>
                        <div class="trip-meta">
                            {{ $upcomingTraining->training_date->format('D, d M Y') }}
                            · {{ $upcomingTraining->duration_days }} day(s)
                            @if($upcomingTraining->provider) · {{ $upcomingTraining->provider }} @endif
                        </div>
                    </div>
                    <span class="status-pill status-training">
                        {{ ucfirst($upcomingTraining->status) }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        {{-- RECENT RATINGS --}}
        @php $recentRatings = auth()->user()->ratings()->with('ratedBy','trip')->latest()->take(3)->get(); @endphp
        @if($recentRatings->count())
        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent ratings</div>
                <span style="color:var(--amber);font-weight:600">★ {{ $stats['avg_rating'] }}</span>
            </div>
            @foreach($recentRatings as $rating)
            <div style="padding:12px 20px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                    <div style="color:var(--amber);font-size:16px;letter-spacing:2px">
                        {{ str_repeat('★', $rating->rating) }}{{ str_repeat('☆', 5 - $rating->rating) }}
                    </div>
                    <div style="font-size:22px;font-weight:700;color:var(--amber)">{{ $rating->rating }}</div>
                </div>
                @if($rating->comment)
                <div style="font-size:12px;color:var(--text2);font-style:italic;margin-bottom:4px">
                    "{{ $rating->comment }}"
                </div>
                @endif
                <div class="trip-meta">
                    By {{ $rating->ratedBy->name }} · #{{ $rating->trip->trip_code }} · {{ $rating->created_at->diffForHumans() }}
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@endsection

@push('styles')
<style>
.route-timeline { position:relative; }
.route-stop { display:flex; gap:12px; padding-bottom:12px; position:relative; }
.route-stop:not(.last)::before {
    content:''; position:absolute; left:7px; top:16px;
    width:2px; bottom:0; background:var(--border2);
}
.route-dot {
    width:16px; height:16px; border-radius:50%;
    background:var(--accent); border:2px solid var(--bg2);
    flex-shrink:0; margin-top:2px;
}
.route-stop.last .route-dot { background:var(--green); }
.route-name { font-size:13px; font-weight:500; }
.route-time { font-size:11px; color:var(--text3); margin-top:2px; }
</style>
@endpush
