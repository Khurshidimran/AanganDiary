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
    ) {
    }

    public function createDraftFor(Order $order): ?PurchaseOrder
    {
        $vendor = Vendor::oldest('created_at')->first();

        if (! $vendor) {
            return null;
        }

        $warehouseId = $this->settings->group('inventory')->get('default_warehouse_id');
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        if (! $warehouse) {
            return null;
        }

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

        if (empty($quantities)) {
            return null;
        }

        $po = PurchaseOrder::create([
            'po_number' => $this->nextPoNumber(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
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

    private function nextPoNumber(): string
    {
        return 'PO-'.str_pad((string) (PurchaseOrder::withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
