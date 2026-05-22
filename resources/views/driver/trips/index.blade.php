@extends('layouts.driver')
@section('page-title', 'My Trip History')

@section('content')

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total completed</div>
        <div class="stat-value text-accent">{{ $stats['total_completed'] }}</div>
        <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">This month</div>
        <div class="stat-value text-green">{{ $stats['this_month'] }}</div>
        <span class="stat-badge badge-green">{{ now()->format('F Y') }}</span>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg rating</div>
        <div class="stat-value text-amber">
            {{ $stats['avg_rating'] > 0 ? $stats['avg_rating'] . ' ★' : 'N/A' }}
        </div>
        <div class="stat-sub">From {{ $stats['total_ratings'] }} reviews</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total earned</div>
        <div class="stat-value text-teal" style="font-size:20px">₦{{ number_format($stats['total_earned']) }}</div>
        <div class="stat-sub">All completed trips</div>
    </div>
</div>

{{-- FILTERS --}}
<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('driver.trips.index') }}"
            style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <div class="search-wrap">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input class="search-input" name="search"
                    placeholder="Search trips, passenger…"
                    value="{{ request('search') }}" style="width:200px">
            </div>
            <select name="status" class="form-select" style="width:150px">
                <option value="">All statuses</option>
                @foreach(['pending','approved','in_progress','completed','rejected','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>
                    {{ ucfirst(str_replace('_',' ',$s)) }}
                </option>
                @endforeach
            </select>
            <select name="month" class="form-select" style="width:150px">
                <option value="">All months</option>
                @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ request('month')==$m?'selected':'' }}>
                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                </option>
                @endfor
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['status','search','month']))
            <a href="{{ route('driver.trips.index') }}" class="btn btn-ghost btn-sm">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- TRIPS TABLE --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">All my trips</div>
            <div class="card-sub">{{ $trips->total() }} trips found</div>
        </div>
        <a href="{{ route('driver.dashboard') }}" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Trip ID</th>
                <th>Passenger</th>
                <th>Vehicle</th>
                <th>Reason</th>
                <th>Destination</th>
                <th>Scheduled</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Rating</th>
                <th>Pay</th>
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
                    <div class="td-name">{{ $trip->passenger->name }}</div>
                    <div style="font-size:11px;color:var(--text3)">
                        {{ ucfirst($trip->passenger->role) }}
                    </div>
                </td>
                <td>
                    <div style="font-family:'DM Mono',monospace;font-size:12px">
                        {{ $trip->vehicle->plate_number }}
                    </div>
                    <div style="font-size:11px;color:var(--text3)">
                        {{ str_replace('_',' ',ucfirst($trip->vehicle->type)) }}
                    </div>
                </td>
                <td style="max-width:160px">
                    <div style="font-size:12px;color:var(--text2)">
                        {{ Str::limit($trip->reason, 45) }}
                    </div>
                </td>
                <td class="text-muted" style="font-size:12px">
                    {{ $trip->destination ?? '—' }}
                </td>
                <td>
                    <div style="font-size:13px">{{ $trip->scheduled_at->format('d M Y') }}</div>
                    <div style="font-size:11px;color:var(--text3)">{{ $trip->scheduled_at->format('H:i') }}</div>
                </td>
                <td class="text-muted" style="font-size:12px">
                    @if($trip->started_at && $trip->completed_at)
                        {{ $trip->started_at->diffForHumans($trip->completed_at, true) }}
                    @else
                        —
                    @endif
                </td>
                <td>
                    <span class="status-pill status-{{ $trip->status }}">
                        {{ ucfirst(str_replace('_',' ',$trip->status)) }}
                    </span>
                </td>
                <td>
                    @if($trip->rating)
                        <div style="color:var(--amber);font-size:13px;letter-spacing:1px">
                            {{ str_repeat('★', $trip->rating->rating) }}
                        </div>
                        <div style="font-size:11px;color:var(--text3)">
                            {{ $trip->rating->rating }}/5
                        </div>
                        @if($trip->rating->comment)
                        <div style="font-size:10px;color:var(--text3);max-width:100px;
                            overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px"
                            title="{{ $trip->rating->comment }}">
                            "{{ $trip->rating->comment }}"
                        </div>
                        @endif
                    @else
                        <span class="text-muted" style="font-size:12px">Not rated</span>
                    @endif
                </td>
                <td>
                    @if($trip->isCompleted())
                        <span class="{{ $trip->payment_processed ? 'text-green' : 'text-amber' }}"
                            style="font-size:12px;font-weight:500">
                            {{ $trip->payment_processed ? '✓ Paid' : '⏳ Pending' }}
                        </span>
                        <div style="font-size:11px;color:var(--text3);margin-top:1px">
                            ₦{{ number_format(auth()->user()->driverProfile?->per_trip_rate ?? 1200) }}
                        </div>
                    @else
                        <span class="text-muted" style="font-size:12px">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10">
                    <div style="padding:48px;text-align:center">
                        <div style="font-size:40px;margin-bottom:12px">🚗</div>
                        <div style="font-size:14px;font-weight:500;margin-bottom:6px">No trips found</div>
                        <div style="font-size:13px;color:var(--text3)">
                            Try adjusting your filters or check back later.
                        </div>
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

@endsection
