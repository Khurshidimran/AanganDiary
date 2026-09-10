<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Core "receive stock against a PO" logic — shared by the manual Receive
 * Stock screen (PurchaseReceiptController) and AutoPurchaseOrderService's
 * automatic receiving at delivery time, so both paths update StockBalance,
 * PurchaseOrderItem.quantity_received, and the PO's own status identically.
 *
 * Deliberately does NOT check canReceiveStock() or post the accounting
 * entry — callers handle both, since they differ per caller (HTTP-level
 * guard vs. an internal one; accounting posting happens after so the
 * receipt can be audit-logged first, same as the original controller flow).
 */
class PurchaseReceiptService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    /**
     * @param  list<array{purchase_order_item_id: string, quantity: float, unit_cost: float, batch_number?: ?string, manufacturing_date?: ?string, expiry_date?: ?string}>  $items
     */
    public function receive(
        PurchaseOrder $purchaseOrder,
        Carbon|string $receiptDate,
        array $items,
        ?string $receivedBy = null,
        ?string $invoiceNumber = null,
        ?string $notes = null,
    ): PurchaseReceipt {
        return DB::transaction(function () use ($purchaseOrder, $receiptDate, $items, $receivedBy, $invoiceNumber, $notes) {
            $receipt = PurchaseReceipt::create([
                'receipt_number' => $this->nextReceiptNumber(),
                'purchase_order_id' => $purchaseOrder->id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'receipt_date' => $receiptDate,
                'invoice_number' => $invoiceNumber,
                'notes' => $notes,
                'received_by' => $receivedBy,
            ]);

            $totalCost = 0;

            foreach ($items as $item) {
                $poItem = $purchaseOrder->items()->findOrFail($item['purchase_order_item_id']);
                $lineCost = $item['quantity'] * $item['unit_cost'];
                $totalCost += $lineCost;

                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_variant_id' => $poItem->product_variant_id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineCost,
                    'batch_number' => $item['batch_number'] ?? null,
                    'manufacturing_date' => $item['manufacturing_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $poItem->increment('quantity_received', $item['quantity']);

                $this->inventory->postTransaction(
                    variant: $poItem->productVariant,
                    warehouse: $purchaseOrder->warehouse,
                    transactionType: InventoryTransaction::TYPE_PURCHASE_RECEIPT,
                    quantity: (float) $item['quantity'],
                    batchNumber: $item['batch_number'] ?? null,
                    referenceType: 'purchase_receipt',
                    referenceId: $receipt->id,
                    notes: "Received against {$purchaseOrder->po_number}",
                    expiryDate: $item['expiry_date'] ?? null,
                );
            }

            $receipt->update(['total_cost' => $totalCost]);

            $purchaseOrder->refresh()->load('items');
            $allReceived = $purchaseOrder->items->every(fn ($i) => $i->quantityRemaining() <= 0);
            $someReceived = $purchaseOrder->items->sum('quantity_received') > 0;

            $purchaseOrder->update([
                'status' => $allReceived
                    ? PurchaseOrder::STATUS_FULLY_RECEIVED
                    : ($someReceived ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : $purchaseOrder->status),
            ]);

            return $receipt;
        });
    }

    /**
     * Reverses a receipt — its own stock and quantity_received contribution
     * only (never anything from a different receipt against the same PO) —
     * and soft-deletes it. The accounting entry is voided separately by the
     * caller (AccountingPostingService::voidPurchaseEntry) since that's a
     * distinct concern with its own audit trail.
     *
     * Throws InsufficientStockException (uncaught, left to the caller) if
     * this receipt's stock has already been consumed by something else
     * since it was received — unposting can't reverse stock that's no
     * longer there.
     */
    public function unpost(PurchaseReceipt $receipt, string $reason): void
    {
        DB::transaction(function () use ($receipt, $reason) {
            $receipt->loadMissing('items.productVariant', 'items.purchaseOrderItem', 'purchaseOrder.items');
            $purchaseOrder = $receipt->purchaseOrder;

            foreach ($receipt->items as $item) {
                $this->inventory->postTransaction(
                    variant: $item->productVariant,
                    warehouse: $receipt->warehouse,
                    transactionType: InventoryTransaction::TYPE_PURCHASE_RECEIPT_REVERSAL,
                    quantity: -(float) $item->quantity,
                    batchNumber: $item->batch_number,
                    referenceType: 'purchase_receipt',
                    referenceId: $receipt->id,
                    notes: "Unposted {$receipt->receipt_number}: {$reason}",
                );

                $item->purchaseOrderItem->decrement('quantity_received', $item->quantity);
            }

            $purchaseOrder->refresh()->load('items');
            $someReceived = $purchaseOrder->items->sum('quantity_received') > 0;

            // Nothing left received on this PO at all -> back to fully
            // editable Draft, so staff can actually fix a wrong quantity or
            // cost before receiving again. If other items/receipts still
            // have stock received, only step back to Partially Received —
            // reopening the whole PO to Draft would disrupt those too.
            $purchaseOrder->update([
                'status' => $someReceived ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : PurchaseOrder::STATUS_DRAFT,
            ]);

            $receipt->delete();
        });
    }

    private function nextReceiptNumber(): string
    {
        $next = PurchaseReceipt::withTrashed()->count() + 1;

        return 'GRN-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
