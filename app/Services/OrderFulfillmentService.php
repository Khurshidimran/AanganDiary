<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Commits/releases stock against the order lifecycle. Deliberately not tied to
 * webhook receipt — allocation only happens when an order is explicitly
 * confirmed, so a "pending" order never locks stock before staff have reviewed
 * it. Bundle products (Product::is_bundle) carry no stock of their own; selling
 * one expands into deducting each of its bundle_items component variants instead.
 */
class OrderFulfillmentService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function allocateStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $warehouse = $this->defaultWarehouse();
            $order->load('items.productVariant.product.bundleItems.componentVariant.product');

            foreach ($order->items as $item) {
                foreach ($this->expandToComponents($item) as [$variant, $quantity]) {
                    if (! $variant->product->track_inventory) {
                        continue;
                    }

                    $this->allocateVariant($variant, $warehouse, $quantity, $order);
                }
            }
        });
    }

    /**
     * Reverses whatever was actually posted at allocation time — replaying the
     * ledger rather than recomputing bundle expansion/batch selection again, so
     * a release is always exactly correct regardless of how allocation split
     * quantity across batches.
     */
    public function releaseStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $allocations = InventoryTransaction::where('reference_type', 'orders')
                ->where('reference_id', $order->id)
                ->where('transaction_type', InventoryTransaction::TYPE_ORDER_ALLOCATION)
                ->get();

            foreach ($allocations as $allocation) {
                $this->inventory->postTransaction(
                    variant: $allocation->productVariant,
                    warehouse: $allocation->warehouse,
                    transactionType: InventoryTransaction::TYPE_ORDER_RELEASE,
                    quantity: -$allocation->quantity,
                    batchNumber: $allocation->batch_number ?: null,
                    referenceType: 'orders',
                    referenceId: $order->id,
                    notes: "Order #{$order->shopify_order_number} cancelled",
                );
            }
        });
    }

    /**
     * Non-batch-tracked variants post a single transaction against the default
     * (empty) batch bucket. Batch-tracked variants deduct FEFO (earliest expiry
     * first) across whatever batches exist, splitting the transaction per batch
     * — there's no other automatic batch-selection convention in this app since
     * every other stock-out flow (transfers, adjustments) has a human pick the
     * batch explicitly; orders are fully automated, so FEFO is the safe default.
     */
    private function allocateVariant(ProductVariant $variant, Warehouse $warehouse, float $quantity, Order $order): void
    {
        if (! $variant->product->track_batch) {
            $this->inventory->postTransaction(
                variant: $variant,
                warehouse: $warehouse,
                transactionType: InventoryTransaction::TYPE_ORDER_ALLOCATION,
                quantity: -$quantity,
                referenceType: 'orders',
                referenceId: $order->id,
                notes: "Order #{$order->shopify_order_number}",
            );

            return;
        }

        $remaining = $quantity;

        $batches = StockBalance::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (float) $batch->quantity);

            $this->inventory->postTransaction(
                variant: $variant,
                warehouse: $warehouse,
                transactionType: InventoryTransaction::TYPE_ORDER_ALLOCATION,
                quantity: -$take,
                batchNumber: $batch->batch_number ?: null,
                referenceType: 'orders',
                referenceId: $order->id,
                notes: "Order #{$order->shopify_order_number}",
            );

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new InsufficientStockException(
                "Insufficient stock for {$variant->sku} at {$warehouse->name}: short by {$remaining}.",
            );
        }
    }

    /**
     * Standard-cost COGS for an order — sums each track_inventory component
     * variant's current purchase_price * quantity, expanding bundles the
     * same way allocateStock() does. This app has no per-batch actual-cost
     * tracking (allocation only picks batches FEFO by expiry, not cost), so
     * standard costing off ProductVariant::purchase_price is the honest
     * ceiling here, not true FIFO/actual cost.
     */
    public function costOfGoodsSold(Order $order): float
    {
        $order->loadMissing('items.productVariant.product.bundleItems.componentVariant.product');

        $total = 0.0;

        foreach ($order->items as $item) {
            foreach ($this->expandToComponents($item) as [$variant, $quantity]) {
                if (! $variant->product->track_inventory) {
                    continue;
                }

                $total += (float) $variant->purchase_price * $quantity;
            }
        }

        return $total;
    }

    /**
     * @return list<array{0: ProductVariant, 1: float}>
     */
    private function expandToComponents(OrderItem $item): array
    {
        $variant = $item->productVariant;

        if (! $variant) {
            return [];
        }

        if (! $variant->product->is_bundle) {
            return [[$variant, (float) $item->quantity]];
        }

        return $variant->product->bundleItems
            ->map(fn ($bundleItem) => [
                $bundleItem->componentVariant,
                (float) $bundleItem->quantity * (float) $item->quantity,
            ])
            ->all();
    }

    private function defaultWarehouse(): Warehouse
    {
        $warehouseId = app(SettingsService::class)->group('inventory')->get('default_warehouse_id');

        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        if (! $warehouse) {
            throw new WarehouseNotConfiguredException(
                'No default warehouse is configured. Set one in Settings before confirming orders.',
            );
        }

        return $warehouse;
    }
}
