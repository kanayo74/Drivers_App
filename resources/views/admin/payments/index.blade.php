{{-- ════════════════════════════════════════
  admin/payments/index.blade.php
════════════════════════════════════════ --}}
@extends('layouts.admin')
@section('page-title','Driver Payments')
@section('content')

<div class="payment-header">
    <div>
        <span class="weekend-badge">{{ $isWeekend ? '✓ Weekend — Payments open' : 'Weekday — Payments locked' }}</span>
        <div style="font-size:20px;font-weight:600;margin-top:10px;margin-bottom:4px">Outstanding driver payments</div>
        <div style="font-size:13px;color:var(--text3)">Payments are processed on Saturdays and Sundays only.</div>
    </div>
    <div style="text-align:right">
        <div class="pay-sub">Total outstanding</div>
        <div class="pay-amount">₦{{ number_format($outstanding->sum('amount_due')) }}</div>
        <div class="pay-sub">{{ $outstanding->count() }} drivers</div>
        @if($isWeekend)
        <form method="POST" action="{{ route('admin.payments.pay-all') }}" style="margin-top:10px">
            @csrf
            <button class="btn btn-green">Pay all drivers now</button>
        </form>
        @endif
    </div>
</div>

@if(!$isWeekend)
<div class="alert alert-amber">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Today is a weekday. Payment processing will be available on Saturday and Sunday.
</div>
@endif

<div class="card">
    <div class="card-header"><div class="card-title">Unpaid drivers</div></div>
    <table>
        <thead><tr><th>Driver</th><th>Unpaid trips</th><th>Rate / trip</th><th>Amount due</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($outstanding as $row)
            <tr>
                <td class="td-name">{{ $row['driver']->name }}</td>
                <td>{{ $row['unpaid_trips'] }}</td>
                <td>₦{{ number_format($row['rate']) }}</td>
                <td style="font-weight:600;color:var(--teal)">₦{{ number_format($row['amount_due']) }}</td>
                <td>
                    @if($isWeekend)
                    <form method="POST" action="{{ route('admin.payments.pay', $row['driver']) }}">
                        @csrf
                        <button class="btn btn-green btn-sm">Pay ₦{{ number_format($row['amount_due']) }}</button>
                    </form>
                    @else
                    <button class="btn btn-ghost btn-sm" disabled style="opacity:.4">Weekend only</button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state">All drivers have been paid. 🎉</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Payment history</div></div>
    <table>
        <thead><tr><th>Driver</th><th>Amount</th><th>Trips</th><th>Period</th><th>Reference</th><th>Paid at</th><th>By</th></tr></thead>
        <tbody>
            @forelse($history as $pay)
            <tr>
                <td class="td-name">{{ $pay->driver->name }}</td>
                <td style="font-weight:600;color:var(--teal)">₦{{ number_format($pay->total_amount) }}</td>
                <td>{{ $pay->trips_count }}</td>
                <td class="text-muted" style="font-size:12px">{{ $pay->period_start->format('d M') }} – {{ $pay->period_end->format('d M Y') }}</td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--text3)">{{ $pay->payment_reference }}</td>
                <td class="text-muted" style="font-size:12px">{{ $pay->paid_at?->format('d M Y H:i') }}</td>
                <td class="text-muted">{{ $pay->processedBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-state">No payment history yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 20px">{{ $history->links() }}</div>
</div>
@endsection
