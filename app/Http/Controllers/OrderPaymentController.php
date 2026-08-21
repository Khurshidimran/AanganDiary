<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderPaymentRequest;
use App\Models\Order;
use App\Services\AccountingPostingService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OrderPaymentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function store(StoreOrderPaymentRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $payment = DB::transaction(function () use ($order, $validated) {
            $payment = $order->payments()->create([
                ...$validated,
                'created_by' => auth()->id(),
            ]);

            // Floored at 0 — the FormRequest already caps the amount at the
            // current outstanding balance, this is just defensive against a
            // race between two payments recorded at the same instant.
            $remaining = max(0, (float) $order->total_outstanding - (float) $validated['amount']);

            $order->update([
                'total_outstanding' => $remaining,
                'payment_status' => $remaining <= 0 ? Order::PAYMENT_STATUS_PAID : Order::PAYMENT_STATUS_PARTIALLY_PAID,
            ]);

            return $payment;
        });

        $this->auditLog->log('created', 'order_payments', $payment, null, $payment->only(['amount', 'payment_date']));

        $entry = $this->accounting->postCustomerPaymentEntry($payment);
        $accountingNote = $entry ? '' : ' Accounting entry was not posted — finish Account Mapping setup.';

        return redirect()->route('orders.show', $order)->with('status', 'Payment recorded successfully.'.$accountingNote);
    }
}
