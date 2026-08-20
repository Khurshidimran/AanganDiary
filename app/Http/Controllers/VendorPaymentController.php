<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorPaymentRequest;
use App\Models\Vendor;
use App\Services\AccountingPostingService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class VendorPaymentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function store(StoreVendorPaymentRequest $request, Vendor $vendor): RedirectResponse
    {
        $payment = $vendor->payments()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $this->auditLog->log('created', 'vendor-payments', $payment, null, $payment->only(['amount', 'payment_date']));

        $entry = $this->accounting->postVendorPaymentEntry($payment);
        $accountingNote = $entry ? '' : ' Accounting entry was not posted — finish Account Mapping setup.';

        return redirect()->route('vendors.show', $vendor)->with('status', 'Payment recorded successfully.'.$accountingNote);
    }
}
