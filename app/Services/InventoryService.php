<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /**
     * Post a stock movement for a variant at a warehouse (optionally batch-specific),
     * updating the running stock balance and appending an immutable ledger entry.
     *
     * Must be called inside a DB transaction — it row-locks the stock balance to
     * prevent concurrent postings from racing on the same before/after quantities.
     */
    public function postTransaction(
        ProductVariant $variant,
        Warehouse $warehouse,
        string $transactionType,
        float $quantity,
        ?string $batchNumber = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $notes = null,
        ?string $expiryDate = null,
        bool $allowNegative = false,
    ): InventoryTransaction {
        $batchNumber ??= '';

        $balance = StockBalance::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('batch_number', $batchNumber)
            ->lockForUpdate()
            ->first();

        $quantityBefore = $balance?->quantity ?? 0;
        $quantityAfter = $quantityBefore + $quantity;

        if (! $allowNegative && $quantityAfter < 0) {
            throw new InsufficientStockException(
                "Insufficient stock for {$variant->sku} at {$warehouse->name}".($batchNumber ? " (batch {$batchNumber})" : '').
                ": {$quantityBefore} available, {$quantity} requested.",
            );
        }

        if ($balance) {
            $balance->update([
                'quantity' => $quantityAfter,
                'expiry_date' => $expiryDate ?? $balance->expiry_date,
            ]);
        } else {
            StockBalance::create([
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
                'batch_number' => $batchNumber,
                'quantity' => $quantityAfter,
                'expiry_date' => $expiryDate,
            ]);
        }

        return InventoryTransaction::create([
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => $batchNumber,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => Auth::id(),
            'notes' => $notes,
            'transaction_date' => now(),
        ]);
    }
}
