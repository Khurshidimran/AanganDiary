<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\AutoPurchaseOrderService;
use Illuminate\Database\Seeder;

/**
 * One-off catch-up: creates the auto-generated Purchase Order for every
 * order placed between 2026-09-01 and 2026-09-08 that doesn't already have
 * one — this window predates AutoPurchaseOrderService actually being wired
 * into order creation, so these orders never got one at the time. Any of
 * those orders that are already delivered also get their (now-created)
 * draft PO auto-received immediately — inventory and accounting updated —
 * matching exactly what would have happened automatically had the feature
 * existed when they were delivered.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=SeptemberBackfillPurchaseOrderSeeder
 *
 * Safe to re-run: createDraftFor() only acts on an order with zero linked
 * purchase orders, and autoReceiveForOrder() only acts on a PO still
 * sitting in Draft — an order already handled by an earlier run of this
 * script (or by the live app in the meantime) is simply skipped, not
 * duplicated.
 */
class SeptemberBackfillPurchaseOrderSeeder extends Seeder
{
    private const FROM_DATE = '2026-09-01 00:00:00';

    private const TO_DATE = '2026-09-08 23:59:59';

    public function run(): void
    {
        $service = app(AutoPurchaseOrderService::class);

        $orders = Order::whereBetween('shopify_created_at', [self::FROM_DATE, self::TO_DATE])
            ->where('order_status', '!=', Order::ORDER_STATUS_CANCELLED)
            ->whereDoesntHave('purchaseOrders')
            ->get();

        $created = 0;
        $received = 0;
        $nothingToPurchase = 0;

        foreach ($orders as $order) {
            $po = $service->createDraftFor($order);

            if (! $po) {
                // No vendor/default warehouse configured, or nothing on the
                // order is actually purchasable (e.g. every line is a
                // non-stocked product) — same tolerance the live flow has.
                $nothingToPurchase++;

                continue;
            }

            $created++;

            if ($order->delivery_status === Order::DELIVERY_STATUS_DELIVERED) {
                $service->autoReceiveForOrder($order);
                $received++;
            }
        }

        $this->command?->info(
            "Checked {$orders->count()} order(s) missing a purchase order. ".
            "Created {$created}, of which {$received} were auto-received (already delivered). ".
            "{$nothingToPurchase} had nothing purchasable to create a PO for."
        );
    }
}
