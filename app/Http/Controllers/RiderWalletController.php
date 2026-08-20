<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayRiderEarningsRequest;
use App\Http\Requests\RecordCashDepositRequest;
use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use App\Services\AuditLogService;
use App\Services\RiderWalletService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderWalletController extends Controller
{
    public function __construct(
        private readonly RiderWalletService $wallet,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function recordCashDeposit(RecordCashDepositRequest $request, RiderProfile $rider): RedirectResponse
    {
        $this->authorize('rider_wallet.manage');

        $validated = $request->validated();

        DB::transaction(function () use ($rider, $validated) {
            $this->wallet->postTransaction(
                rider: $rider,
                transactionType: RiderWalletTransaction::TYPE_COD_SETTLED,
                amount: -$validated['amount'],
                notes: $validated['notes'] ?? 'Cash deposit recorded',
                paymentMethod: $validated['payment_method'],
                referenceNumber: $validated['reference_number'] ?? null,
                transactionDate: Carbon::parse($validated['deposit_date']),
            );
        });

        $this->auditLog->log('cash_deposited', 'riders', $rider, null, $validated);

        return back()->with('status', 'Cash deposit recorded.');
    }

    public function payRider(PayRiderEarningsRequest $request, RiderProfile $rider): RedirectResponse
    {
        $this->authorize('rider_wallet.manage');

        $validated = $request->validated();

        DB::transaction(function () use ($rider, $validated) {
            $this->wallet->postTransaction(
                rider: $rider,
                transactionType: RiderWalletTransaction::TYPE_EARNING_PAID,
                amount: $validated['amount'],
                notes: $validated['notes'] ?? 'Earnings paid out to rider',
                paymentMethod: $validated['payment_method'],
                referenceNumber: $validated['reference_number'] ?? null,
                transactionDate: Carbon::parse($validated['payment_date']),
            );
        });

        $this->auditLog->log('earnings_paid', 'riders', $rider, null, $validated);

        return back()->with('status', 'Earnings payout recorded.');
    }

    public function adjust(Request $request, RiderProfile $rider): RedirectResponse
    {
        $this->authorize('rider_wallet.manage');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($rider, $validated) {
            $this->wallet->postTransaction(
                rider: $rider,
                transactionType: RiderWalletTransaction::TYPE_ADJUSTMENT,
                amount: $validated['amount'],
                notes: $validated['notes'],
            );
        });

        $this->auditLog->log('wallet_adjusted', 'riders', $rider, null, $validated);

        return back()->with('status', 'Wallet adjustment recorded.');
    }
}
