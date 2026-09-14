<?php

namespace Database\Seeders;

use App\Models\JournalEntry;
use App\Models\Order;
use App\Services\AccountMappingService;
use App\Services\JournalEntryService;
use Illuminate\Database\Seeder;

/**
 * One-off manual COGS for the last 2 orders from the September Sales-vs-
 * COGS audit: #9683 and #9805 both sold "50/50 shredded 1 kg" — a walk-in
 * item the dispatch manager confirmed was entered manually on the order and
 * was never in the product catalog, so there's no ProductVariant/
 * purchase_price for postCogsEntry() to compute a cost from automatically.
 *
 * Confirmed a one-off (unlikely to be sold again), so no catalog entry was
 * created for it — just this direct journal posting at the confirmed real
 * cost (Rs 1,589/kg, 1kg on each order), same accounts and dating
 * (delivered_at) as every automatically-posted COGS entry.
 *
 * Idempotent — skips an order that already has a posted order_cogs entry,
 * so safe to re-run.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=PostManualCogsFor9683And9805Seeder --force
 */
class PostManualCogsFor9683And9805Seeder extends Seeder
{
    private const COST_PER_KG = 1589.00;

    private const ORDERS = [
        '#9683' => 1.0,
        '#9805' => 1.0,
    ];

    public function run(): void
    {
        $mapping = app(AccountMappingService::class);
        $journal = app(JournalEntryService::class);

        $cogsAccount = $mapping->cogsAccount();
        $inventoryAccount = $mapping->inventoryAssetAccount();

        if (! $cogsAccount || ! $inventoryAccount) {
            $this->command?->error('COGS or Inventory Asset account not mapped — configure Account Mapping first.');

            return;
        }

        foreach (self::ORDERS as $orderNumber => $quantityKg) {
            $order = Order::where('shopify_order_number', $orderNumber)->first();

            if (! $order) {
                $this->command?->error("{$orderNumber} not found — skipped.");

                continue;
            }

            if ($journal->hasPostedEntryFor('order_cogs', $order->id)) {
                $this->command?->info("{$orderNumber} already has a posted COGS entry — left alone.");

                continue;
            }

            $amount = round(self::COST_PER_KG * $quantityKg, 2);

            $entry = $journal->postSimple(
                type: JournalEntry::TYPE_JOURNAL,
                entryDate: $order->delivered_at->toDateString(),
                debitAccount: $cogsAccount,
                creditAccount: $inventoryAccount,
                amount: $amount,
                narration: "COGS — Order {$orderNumber} — 50/50 Shredded 1kg (manual walk-in item, not in product catalog)",
                referenceType: 'order_cogs',
                referenceId: $order->id,
                source: JournalEntry::SOURCE_MANUAL,
            );

            $this->command?->info("{$orderNumber}: posted {$entry->entry_number} for Rs {$amount}");
        }
    }
}
