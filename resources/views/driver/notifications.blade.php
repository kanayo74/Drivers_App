{{-- resources/views/driver/notifications.blade.php --}}
@extends('layouts.driver')
@section('page-title', 'Notifications')
@section('content')
<div class="card">
    <div class="card-header">
        <div><div class="card-title">My notifications</div><div class="card-sub">{{ $notifications->total() }} total</div></div>
    </div>
    @forelse($notifications as $n)
    @php
        $data = $n->data; $read = $n->read_at !== null;
        $icons = ['trip_request'=>'🚗','trip_approved'=>'✅','trip_rejected'=>'❌','fuel_acknowledged'=>'⛽','training_assigned'=>'📚','trip_cancelled'=>'🚫'];
        $icon  = $icons[$data['type']??'']??'🔔';
    @endphp
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;gap:14px;
        background:{{ $read?'transparent':'rgba(79,124,255,.04)' }};
        border-left:{{ $read?'3px solid transparent':'3px solid var(--accent)' }}">
        <div style="font-size:22px;flex-shrink:0">{{ $icon }}</div>
        <div style="flex:1">
            <div style="font-weight:{{ $read?'400':'600' }};font-size:13px;color:var(--text)">{{ $data['title']??'Notification' }}</div>
            <div style="font-size:13px;color:var(--text2);margin-top:3px;line-height:1.5">{{ $data['message']??'' }}</div>
            <div style="font-size:11px;color:var(--text3);margin-top:6px">{{ $n->created_at->diffForHumans() }}</div>
        </div>
        @if(isset($data['url']))<a href="{{ $data['url'] }}" class="btn btn-ghost btn-sm">View →</a>@endif
    </div>
    @empty
    <div class="empty-state" style="padding:48px">
        <div style="font-size:36px;margin-bottom:12px">🔔</div>
        <div style="font-size:14px;font-weight:500">No notifications yet.</div>
    </div>
    @endforelse
    <div style="padding:14px 20px">{{ $notifications->links() }}</div>
</div>
@endsection
