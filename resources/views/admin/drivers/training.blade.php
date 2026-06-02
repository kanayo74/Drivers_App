@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <div><div class="card-title">Driver training</div><div class="card-sub">Training records</div></div>
    </div>
    <div style="padding:16px">
        @if(isset($driver))
            @if($driver->training_info)
                <div style="white-space:pre-wrap">{{ $driver->training_info }}</div>
            @else
                <div class="empty-state">No training information available.</div>
            @endif
        @else
            <div class="empty-state">Driver not found.</div>
        @endif
    </div>
</div>
@endsection