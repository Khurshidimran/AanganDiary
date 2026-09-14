<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\AccountingPostingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Backfills COGS for September-delivered orders that have a posted Sales
 * entry but never got a matching COGS entry — found via an audit request
 * checking Sales vs COGS for Sept 1-30.
 *
 * Root cause (fixed in OrderFulfillmentService::costOfGoodsSold(), deploy
 * that change BEFORE running this): the method skipped any variant with
 * track_inventory=false, but several "Deal" products are sold as their own
 * plain, non-tracked SKU (no bundle components configured) with a real
 * purchase_price set directly on them — track_inventory only governs stock
 * movement, not cost, so skipping them understated COGS to zero for every
 * such sale. The fix removes that skip; this seeder posts the entries that
 * were silently never created because of it.
 *
 * Only posts where the (now-fixed) cost calculation comes back > 0 —
 * postCogsEntry() itself no-ops otherwise. Two known categories will still
 * be skipped and need separate resolution, NOT fixed by this seeder:
 *   - tracked products with purchase_price = 0 (needs the real cost entered)
 *   - order line items with no matching ProductVariant at all (unmapped SKU)
 * Run this seeder's output tells you exactly how many were posted vs still
 * skipped so you can see whether that gap matches expectations.
 *
 * Idempotent — postCogsEntry() itself guards against a duplicate for any
 * order that already has one, so a second run posts nothing further.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=BackfillMissingSeptemberCogsSeeder --force
 */
class BackfillMissingSeptemberCogsSeeder extends Seeder
{
    public function run(): void
    {
        $accounting = app(AccountingPostingService::class);

        $orderIds = DB::table('orders as o')
            ->join('journal_entries as js', fn ($join) => $join
                ->on('js.reference_id', '=', 'o.id')
                ->where('js.reference_type', 'orders')
                ->where('js.status', 'posted'))
            ->leftJoin('journal_entries as jc', fn ($join) => $join
                ->on('jc.reference_id', '=', 'o.id')
                ->where('jc.reference_type', 'order_cogs')
                ->where('jc.status', 'posted'))
            ->whereBetween('o.delivered_at', ['2026-09-01 00:00:00', '2026-09-30 23:59:59'])
            ->whereNull('jc.id')
            ->where('o.total', '>', 0)
            ->pluck('o.id');

        $posted = 0;
        $skipped = 0;

        foreach ($orderIds as $orderId) {
            $order = Order::with('items.productVariant.product.bundleItems')->find($orderId);
            $entry = $accounting->postCogsEntry($order);

            $entry ? $posted++ : $skipped++;
        }

        $this->command?->info("Posted {$posted} COGS entries. {$skipped} order(s) still skipped — check for zero purchase_price or unmapped SKUs.");
    }
}
