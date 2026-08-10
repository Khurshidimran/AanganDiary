<?php

namespace App\Services;

use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use Illuminate\Support\Facades\Auth;

/**
 * Posts signed entries to a rider's wallet ledger, mirroring
 * InventoryService::postTransaction's before/after-balance pattern. Unlike
 * stock, a wallet balance is allowed to go negative — that just means the
 * company currently owes the rider (e.g. credited but unpaid earnings).
 */
class RiderWalletService
{
    /**
     * Must be called inside a DB transaction — it row-locks the rider profile
     * to prevent concurrent postings from racing on the same before/after balance.
     */
    public function postTransaction(
        RiderProfile $rider,
        string $transactionType,
        float $amount,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
    ): RiderWalletTransaction {
        $locked = RiderProfile::where('id', $rider->id)->lockForUpdate()->first();

        $balanceBefore = (float) $locked->wallet_balance;
        $balanceAfter = $balanceBefore + $amount;

        $locked->update(['wallet_balance' => $balanceAfter]);

        return RiderWalletTransaction::create([
            'rider_id' => $rider->id,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'recorded_by' => Auth::id(),
            'notes' => $notes,
        ]);
    }
}
