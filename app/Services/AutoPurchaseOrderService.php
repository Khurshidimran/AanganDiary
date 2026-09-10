<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\Warehouse;

/**
 * This business holds no standing inventory — stock is bought reactively,
 * per order, rather than kept on hand in advance. So every new order needs
 * a matching draft Purchase Order for staff to review, adjust the quantities
 * or unit costs on, and push through the normal PO submit -> approve ->
 * receive flow whenever they're ready.
 *
 * Deliberately best-effort: a missing vendor/default warehouse, or an order
 * with nothing actually purchasable (e.g. every line is a non-stocked
 * product), must never block order creation itself — same tolerance
 * OrderFulfillmentService::allocateStock() already gets from its callers.
 */
class AutoPurchaseOrderService
{
    public function __construct(
        private readonly OrderFulfillmentService $fulfillment,
        private readonly SettingsService $settings,
        private readonly PurchaseReceiptService $receiptService,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function createDraftFor(Order $order): ?PurchaseOrder
    {
        $vendor = Vendor::oldest('created_at')->first();
        $warehouse = $this->defaultWarehouse();

        if (! $vendor || ! $warehouse) {
            return null;
        }

        [$quantities, $variantsById] = $this->neededQuantities($order);

        if (empty($quantities)) {
            return null;
        }

        $po = PurchaseOrder::create([
            'po_number' => $this->nextPoNumber(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'source_order_id' => $order->id,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now(),
            'notes' => "Auto-generated for Order #{$order->shopify_order_number} — review quantities and unit costs before submitting.",
        ]);

        foreach ($quantities as $variantId => $quantity) {
            /** @var ProductVariant $variant */
            $variant = $variantsById[$variantId];

            $po->items()->create([
                'product_variant_id' => $variantId,
                'quantity_ordered' => $quantity,
                'unit_cost' => (float) $variant->purchase_price,
            ]);
        }

        return $po;
    }

    /**
     * Called when an already-synced order's line items change (a customer
     * calls in and staff edits the order on Shopify after the fact) — keeps
     * every still-safe-to-touch auto-generated PO's quantities matching
     * what the order now actually needs: existing lines are updated,
     * newly-needed products get a new line, and products no longer on the
     * order have their line removed. A PO that already has ANY stock
     * received against it is left alone entirely — editing quantities on a
     * partially-fulfilled purchase is a decision for a human, not this.
     */
    public function reconcileForOrder(Order $order): void
    {
        [$quantities, $variantsById] = $this->neededQuantities($order);

        foreach ($order->purchaseOrders as $po) {
            if ($po->status === PurchaseOrder::STATUS_CANCELLED) {
                continue;
            }

            if ($po->items->sum('quantity_received') > 0) {
                continue;
            }

            $existingByVariant = $po->items->keyBy('product_variant_id');

            foreach ($quantities as $variantId => $quantity) {
                if ($existingByVariant->has($variantId)) {
                    $existingByVariant[$variantId]->update(['quantity_ordered' => $quantity]);
                } else {
                    /** @var ProductVariant $variant */
                    $variant = $variantsById[$variantId];

                    $po->items()->create([
                        'product_variant_id' => $variantId,
                        'quantity_ordered' => $quantity,
                        'unit_cost' => (float) $variant->purchase_price,
                    ]);
                }
            }

            $po->items()->whereNotIn('product_variant_id', array_keys($quantities))->delete();
        }
    }

    /**
     * Called when an order actually gets delivered (or self-picked-up) —
     * the goods have genuinely left, so this is the right moment to receive
     * the stock that covered it too, rather than requiring staff to
     * manually walk every auto-generated PO through Submit → Approve →
     * Receive Stock for the common case. Deliberately narrow: only a PO
     * still sitting untouched in Draft is auto-advanced — one staff already
     * started working (submitted/approved/edited/partially received) is
     * left alone, since that's a human actively managing it, not this.
     * An order that fails or gets returned never reaches this method at
     * all, so nothing here ever needs undoing for that case.
     */
    public function autoReceiveForOrder(Order $order): void
    {
        foreach ($order->purchaseOrders as $po) {
            if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
                continue;
            }

            $po->loadMissing('items');

            if ($po->items->isEmpty()) {
                continue;
            }

            $po->update(['status' => PurchaseOrder::STATUS_APPROVED]);

            $items = $po->items->map(fn ($item) => [
                'purchase_order_item_id' => $item->id,
                'quantity' => (float) $item->quantity_ordered,
                'unit_cost' => (float) $item->unit_cost,
            ])->all();

            $receipt = $this->receiptService->receive(
                purchaseOrder: $po,
                receiptDate: $order->delivered_at ?? now(),
                items: $items,
                notes: "Auto-received on delivery of Order #{$order->shopify_order_number}",
            );

            $this->accounting->postPurchaseEntry($receipt);
        }
    }

    /**
     * @return array{0: array<string, float>, 1: array<string, ProductVariant>}
     */
    private function neededQuantities(Order $order): array
    {
        $order->loadMissing('items.productVariant.product.bundleItems.componentVariant.product');

        $quantities = [];
        $variantsById = [];

        foreach ($order->items as $item) {
            foreach ($this->fulfillment->expandToComponents($item) as [$variant, $quantity]) {
                if (! $variant->product->track_inventory) {
                    continue;
                }

                $quantities[$variant->id] = ($quantities[$variant->id] ?? 0) + $quantity;
                $variantsById[$variant->id] = $variant;
            }
        }

        return [$quantities, $variantsById];
    }

    private function defaultWarehouse(): ?Warehouse
    {
        $warehouseId = $this->settings->group('inventory')->get('default_warehouse_id');

        return $warehouseId ? Warehouse::find($warehouseId) : null;
    }

    private function nextPoNumber(): string
    {
        return 'PO-'.str_pad((string) (PurchaseOrder::withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
