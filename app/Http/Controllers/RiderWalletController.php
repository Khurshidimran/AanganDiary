<?php

namespace App\Http\Controllers;

use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use App\Services\AuditLogService;
use App\Services\RiderWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RiderWalletController extends Controller
{
    public function __construct(
        private readonly RiderWalletService $wallet,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function show(RiderProfile $rider): View
    {
        $this->authorize('rider_wallet.view');

        $transactions = $rider->walletTransactions()
            ->with('recordedBy')
            ->latest('created_at')
            ->paginate(30);

        return view('riders.wallet', compact('rider', 'transactions'));
    }

    public function settleCod(Request $request, RiderProfile $rider): RedirectResponse
    {
        $this->authorize('rider_wallet.manage');

        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);

        DB::transaction(function () use ($rider, $validated) {
            $this->wallet->postTransaction(
                rider: $rider,
                transactionType: RiderWalletTransaction::TYPE_COD_SETTLED,
                amount: -$validated['amount'],
                notes: 'COD cash settled by rider',
            );
        });

        $this->auditLog->log('cod_settled', 'riders', $rider, null, ['amount' => $validated['amount']]);

        return back()->with('status', 'COD settlement recorded.');
    }

    public function payEarnings(Request $request, RiderProfile $rider): RedirectResponse
    {
        $this->authorize('rider_wallet.manage');

        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);

        DB::transaction(function () use ($rider, $validated) {
            $this->wallet->postTransaction(
                rider: $rider,
                transactionType: RiderWalletTransaction::TYPE_EARNING_PAID,
                amount: $validated['amount'],
                notes: 'Earnings paid out to rider',
            );
        });

        $this->auditLog->log('earnings_paid', 'riders', $rider, null, ['amount' => $validated['amount']]);

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
