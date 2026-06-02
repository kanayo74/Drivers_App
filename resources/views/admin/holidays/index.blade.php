@extends('layouts.admin')
@section('page-title','Public Holidays')
@section('topbar-actions')
<button class="btn btn-primary" onclick="openModal('add-holiday-modal')">+ Add Holiday</button>
@endsection
@section('content')
<div class="alert alert-blue">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Drivers who work on public holidays will be paid just like weekend (Saturday/Sunday) trips.
    Add all Nigerian public holidays here.
</div>
<div class="card">
    <div class="card-header">
        <div><div class="card-title">Public holidays</div><div class="card-sub">{{ $holidays->total() }} entries</div></div>
    </div>
    <table>
        <thead><tr><th>Name</th><th>Date</th><th>Year</th><th>Recurring</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($holidays as $h)
            <tr>
                <td class="td-name">{{ $h->name }}</td>
                <td class="text-muted" style="font-size:12px;font-family:'DM Mono',monospace">{{ $h->date->format('d M Y') }}</td>
                <td class="text-muted">{{ $h->year }}</td>
                <td>
                    @if($h->is_recurring)
                    <span class="badge-sm badge-green" style="margin-top:0">Every year</span>
                    @else
                    <span class="text-muted" style="font-size:12px">Once</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.holidays.store') }}"
                        onsubmit="return confirm('Delete this holiday?')" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-red btn-sm" style="display:none">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state">No holidays added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 20px">{{ $holidays->links() }}</div>
</div>

<div class="modal-bg" id="add-holiday-modal">
    <div class="modal">
        <div class="modal-title">Add public holiday</div>
        <div class="modal-sub">Drivers working on this day will qualify for payment.</div>
        <form method="POST" action="{{ route('admin.holidays.store') }}">
            @csrf
            <div class="form-group"><label class="form-label">Holiday name *</label><input type="text" name="name" class="form-input" placeholder="e.g. Christmas Day, Nigeria Independence Day" required></div>
            <div class="form-group"><label class="form-label">Date *</label><input type="date" name="date" class="form-input" required></div>
            <div class="form-group">
                <label class="remember-label" style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_recurring" value="1" style="width:15px;height:15px;accent-color:var(--accent)">
                    <span style="font-size:13px;color:var(--text2)">Recurring every year (same date)</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('add-holiday-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add holiday</button>
            </div>
        </form>
    </div>
</div>
@endsection