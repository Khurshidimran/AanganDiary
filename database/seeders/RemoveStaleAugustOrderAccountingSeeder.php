<?php

namespace Database\Seeders;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use Illuminate\Database\Seeder;

/**
 * Follow-up to RemovePreSeptemberAccountingSeeder — catches leftover Sales/
 * COGS entries that seeder's entry_date filter missed.
 *
 * Before the delivery-time revenue change, BackfillSalesAccountingSeeder
 * dated a Sales entry by the order's creation date but a COGS entry by its
 * delivery date — an inconsistent basis from the start. For an order
 * created in August but delivered Sept 1-2, that meant its Sales entry
 * landed in August (correctly deleted by the earlier entry_date-based
 * cleanup) while its COGS entry landed in September (survived that cleanup
 * untouched) — leaving COGS expense posted with no matching Sales revenue
 * for that order.
 *
 * This seeder closes that gap the right way: filtered by the ORDER's own
 * creation date (shopify_created_at), not the entry's date, so it catches
 * every leftover Sales/COGS entry for a pre-September order regardless of
 * which date its entry happened to land on.
 *
 * Hard-deletes, same as RemovePreSeptemberAccountingSeeder and for the same
 * reason — these are stale artifacts of the old dual-dating logic (already
 * fixed in AccountingPostingService::postSalesEntry), not real ongoing
 * business events being reversed.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=RemoveStaleAugustOrderAccountingSeeder --force
 */
class RemoveStaleAugustOrderAccountingSeeder extends Seeder
{
    private const CUTOFF_DATE = '2026-09-01';

    public function run(): void
    {
        $orderIds = Order::where('shopify_created_at', '<', self::CUTOFF_DATE)->pluck('id');

        $entryIds = JournalEntry::whereIn('reference_id', $orderIds)
            ->whereIn('reference_type', ['orders', 'order_cogs'])
            ->pluck('id');

        $entryCount = $entryIds->count();
        $lineCount = JournalEntryLine::whereIn('journal_entry_id', $entryIds)->delete();
        JournalEntry::whereIn('id', $entryIds)->delete();

        $this->command?->info("Deleted {$entryCount} journal entry(ies) and {$lineCount} line(s) for orders created before ".self::CUTOFF_DATE.'.');
    }
}
