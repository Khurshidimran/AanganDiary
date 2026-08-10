<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorPaymentRequest;
use App\Models\Vendor;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class VendorPaymentController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function store(StoreVendorPaymentRequest $request, Vendor $vendor): RedirectResponse
    {
        $payment = $vendor->payments()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $this->auditLog->log('created', 'vendor-payments', $payment, null, $payment->only(['amount', 'payment_date']));

        return redirect()->route('vendors.show', $vendor)->with('status', 'Payment recorded successfully.');
    }
}
