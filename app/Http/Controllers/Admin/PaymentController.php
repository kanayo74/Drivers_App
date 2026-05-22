<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, DriverPayment};
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index()
    {
        $isWeekend   = $this->paymentService->isPaymentWindow();
        $outstanding = $this->paymentService->getOutstandingSummary();
        $history     = DriverPayment::with(['driver', 'processedBy'])->latest()->paginate(20);
        return view('admin.payments.index', compact('isWeekend', 'outstanding', 'history'));
    }

    public function pay(Request $request, User $driver)
    {
        try {
            $payment = $this->paymentService->processPayment($driver, auth()->user(), $request->reference);
            return back()->with('success', '₦' . number_format($payment->total_amount) . " paid to {$driver->name}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function payAll()
    {
        try {
            $payments = $this->paymentService->processAllPayments(auth()->user());
            $total    = collect($payments)->sum('total_amount');
            return back()->with('success', count($payments) . " drivers paid. Total: ₦" . number_format($total));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
