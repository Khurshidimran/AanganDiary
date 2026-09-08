<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * One-off catch-up purchase: creates a single Purchase Order (status
 * APPROVED, no receipt) covering the stock that sales since 2026-09-01 have
 * actually consumed, so cost/profit reporting has real COGS to work from.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — this is a one-time,
 * manually-triggered operation, not part of a fresh-install seed. Run once:
 *   php artisan db:seed --class=SeptemberCatchUpPurchaseSeeder
 * Running it twice creates two separate Purchase Orders — it is not
 * idempotent by design (a second catch-up run is a real business decision,
 * not something to silently dedupe).
 *
 * Quantities mirror OrderFulfillmentService::expandToComponents() exactly —
 * a sold bundle expands into its component variants (bundles carry no stock
 * of their own) and non-track_inventory products are skipped, same as real
 * stock allocation does.
 *
 * Every line uses a flat placeholder unit cost (Rs. 500 — no real
 * per-product cost exists anywhere in this system yet). Edit UNIT_COST
 * below before running if a different number is needed; the PO can also
 * still be edited afterward since it stays APPROVED/unposted.
 *
 * Nothing here touches StockBalance or accounting — the Purchase Order sits
 * at status APPROVED with no PurchaseReceipt. It only affects inventory
 * once someone opens it under Purchasing and uses "Receive Stock".
 */
class SeptemberCatchUpPurchaseSeeder extends Seeder
{
    private const UNIT_COST = 500.0;

    private const CUTOFF_DATE = '2026-09-01 00:00:00';

    private const VENDOR_NAME = 'Rehman Dairy';

    private const WAREHOUSE_NAME = 'Main Warehouse';

    public function run(): void
    {
        $vendor = Vendor::where('name', self::VENDOR_NAME)->firstOrFail();
        $warehouse = Warehouse::where('name', self::WAREHOUSE_NAME)->firstOrFail();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->first();

        $quantities = $this->consumedQuantitiesSinceCutoff();

        if ($quantities->isEmpty()) {
            $this->command?->warn('No active, stock-tracked product variants were sold on or after '.self::CUTOFF_DATE.' — nothing to purchase.');

            return;
        }

        $po = PurchaseOrder::create([
            'po_number' => $this->nextPoNumber(),
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseOrder::STATUS_APPROVED,
            'order_date' => self::CUTOFF_DATE,
            'expected_date' => self::CUTOFF_DATE,
            'notes' => 'Catch-up purchase for cost/profit reporting — quantities equal stock consumed by sales since '
                .self::CUTOFF_DATE.'. Flat placeholder unit cost of Rs. '.number_format(self::UNIT_COST, 2)
                .' per unit pending real per-product costs. Stays unposted (no effect on inventory/accounting) until received via Purchasing > Receive Stock.',
            'created_by' => $admin?->id,
        ]);

        foreach ($quantities as $variantId => $quantity) {
            $po->items()->create([
                'product_variant_id' => $variantId,
                'quantity_ordered' => $quantity,
                'unit_cost' => self::UNIT_COST,
            ]);
        }

        $this->command?->info("Created {$po->po_number} — {$quantities->count()} line item(s), status APPROVED, nothing received yet.");
    }

    /**
     * @return Collection<string, float> quantity consumed, keyed by product_variant_id
     */
    private function consumedQuantitiesSinceCutoff(): Collection
    {
        $totals = collect();

        Order::with('items.productVariant.product.bundleItems.componentVariant.product')
            ->where('shopify_created_at', '>', self::CUTOFF_DATE)
            ->where('order_status', '!=', Order::ORDER_STATUS_CANCELLED)
            ->chunkById(200, function ($orders) use ($totals) {
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        foreach ($this->expandToComponents($item) as [$variant, $quantity]) {
                            if (! $variant->is_active || ! $variant->product->track_inventory) {
                                continue;
                            }

                            $totals[$variant->id] = ($totals[$variant->id] ?? 0) + $quantity;
                        }
                    }
                }
            });

        return $totals;
    }

    /**
     * Mirrors OrderFulfillmentService::expandToComponents() — a sold bundle
     * expands into its component variants, a plain item passes through as-is.
     *
     * @return list<array{0: \App\Models\ProductVariant, 1: float}>
     */
    private function expandToComponents($item): array
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

    private function nextPoNumber(): string
    {
        return 'PO-'.str_pad((string) (PurchaseOrder::withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
