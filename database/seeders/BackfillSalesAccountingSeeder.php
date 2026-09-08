<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\AccountingPostingService;
use Illuminate\Database\Seeder;

/**
 * One-off backfill: posts the Sales (Revenue) and COGS journal entries that
 * should have existed for every already-confirmed/delivered order but never
 * did — Shopify's auto-confirm path (ShopifyOrderSyncService::tryAutoConfirm)
 * bypassed AccountingPostingService entirely until that was fixed, so almost
 * every historical order has zero accounting impact despite being confirmed.
 *
 * Safe to run more than once: postSalesEntry()/postCogsEntry() are both
 * idempotent (JournalEntryService::hasPostedEntryFor guards each), so an
 * order that already has an entry is silently skipped, never duplicated.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=BackfillSalesAccountingSeeder
 */
class BackfillSalesAccountingSeeder extends Seeder
{
    public function run(): void
    {
        $accounting = app(AccountingPostingService::class);

        $salesPosted = 0;
        Order::where('order_status', Order::ORDER_STATUS_CONFIRMED)
            ->chunkById(200, function ($orders) use ($accounting, &$salesPosted) {
                foreach ($orders as $order) {
                    if ($accounting->postSalesEntry($order)) {
                        $salesPosted++;
                    }
                }
            });

        $cogsPosted = 0;
        Order::where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)
            ->chunkById(200, function ($orders) use ($accounting, &$cogsPosted) {
                foreach ($orders as $order) {
                    if ($accounting->postCogsEntry($order)) {
                        $cogsPosted++;
                    }
                }
            });

        $this->command?->info("Backfilled {$salesPosted} sales entries and {$cogsPosted} COGS entries.");
    }
}
