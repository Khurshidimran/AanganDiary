<?php

namespace Database\Seeders;

use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use App\Services\AccountingPostingService;
use App\Services\JournalEntryService;
use App\Services\OrderFulfillmentService;
use App\Services\RiderWalletService;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyOrderSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-off correction for order #9912 (shopify_order_id 12346391462255).
 *
 * It was edited in Shopify (one unit of "Cheesy Slice Deal 2" removed) after
 * it had already been delivered here. The orders/updated webhook for that
 * edit never arrived, so the order sat with the stale pre-edit quantity and
 * total — and both its already-posted Sales/COGS journal entries and its
 * rider wallet COD-collected credit were posted against that stale amount.
 *
 * REQUIRES these code fixes to already be deployed before this runs:
 *   - ShopifyOrderSyncService (reads current_quantity/current_total_price,
 *     not the frozen originals — otherwise this just re-syncs the same
 *     stale total)
 *   - JournalEntryService::hasPostedEntryFor (excludes reversal-source
 *     entries — otherwise voidSalesEntry()/voidCogsEntry() leave
 *     postSalesEntry()/postCogsEntry() unable to post the correction)
 *   - Account::balanceAsOf()/balanceBetween() + AccountingReportController
 *     ledger()/trialBalance() (include voided entries in balance sums, so a
 *     void nets to zero against its reversal instead of leaving it
 *     uncancelled)
 *
 * Idempotent — every step checks whether it's already correct before acting,
 * so this is safe to run more than once, or to run against an environment
 * that has already partially self-healed (e.g. a late webhook came through).
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=FixOrder9912ShopifyEditSeeder --force
 */
class FixOrder9912ShopifyEditSeeder extends Seeder
{
    private const SHOPIFY_ORDER_ID = '12346391462255';

    public function run(): void
    {
        $order = Order::with('items')->where('shopify_order_id', self::SHOPIFY_ORDER_ID)->first();

        if (! $order) {
            $this->command?->error('Order not found (shopify_order_id '.self::SHOPIFY_ORDER_ID.'). Nothing to do.');

            return;
        }

        $this->command?->info("Found order {$order->shopify_order_number} — current total {$order->total}.");

        $this->resyncFromShopify($order);
        $order->refresh()->load('items');
        $this->command?->info("After re-sync: total {$order->total}, total_outstanding {$order->total_outstanding}.");

        $this->fixAccounting($order);
        $this->fixCodAmount($order);
        $this->fixRiderWallet($order);

        $this->command?->info('Done.');
    }

    private function resyncFromShopify(Order $order): void
    {
        $payload = app(ShopifyClient::class)->get("orders/{$order->shopify_order_id}.json")['order'] ?? null;

        if (! $payload) {
            $this->command?->error('Could not fetch the order from Shopify — aborting before touching anything.');

            return;
        }

        app(ShopifyOrderSyncService::class)->sync($payload);
        $this->command?->info('Re-synced from Shopify.');
    }

    private function fixAccounting(Order $order): void
    {
        $accounting = app(AccountingPostingService::class);
        $journal = app(JournalEntryService::class);

        $correctSales = (float) $order->total;
        $existingSales = $this->activePostedAmount($journal, 'orders', $order->id);

        if ($existingSales !== null && abs($existingSales - $correctSales) > 0.01) {
            $accounting->voidSalesEntry($order, "Order edited in Shopify — total corrected from {$existingSales} to {$correctSales}");
            $entry = $accounting->postSalesEntry($order);
            $this->command?->info("Sales entry corrected: {$existingSales} -> {$correctSales}".($entry ? " ({$entry->entry_number})" : ' (not posted — check Account Mapping)'));
        } elseif ($existingSales === null && $order->delivery_status === Order::DELIVERY_STATUS_DELIVERED) {
            $entry = $accounting->postSalesEntry($order);
            $this->command?->info('Sales entry posted fresh: '.($entry?->entry_number ?? 'not posted — check Account Mapping'));
        } else {
            $this->command?->info('Sales entry already correct — left alone.');
        }

        $correctCogs = app(OrderFulfillmentService::class)->costOfGoodsSold($order);
        $existingCogs = $this->activePostedAmount($journal, 'order_cogs', $order->id);

        if ($existingCogs !== null && abs($existingCogs - $correctCogs) > 0.01) {
            $accounting->voidCogsEntry($order, "Order edited in Shopify — COGS corrected from {$existingCogs} to {$correctCogs}");
            $entry = $accounting->postCogsEntry($order);
            $this->command?->info("COGS entry corrected: {$existingCogs} -> {$correctCogs}".($entry ? " ({$entry->entry_number})" : ' (not posted — check Account Mapping)'));
        } elseif ($existingCogs === null && $order->delivery_status === Order::DELIVERY_STATUS_DELIVERED) {
            $entry = $accounting->postCogsEntry($order);
            $this->command?->info('COGS entry posted fresh: '.($entry?->entry_number ?? 'not posted — check Account Mapping'));
        } else {
            $this->command?->info('COGS entry already correct — left alone.');
        }
    }

    /**
     * The currently-active (posted, non-reversal) entry's amount for a
     * reference, or null if there isn't one. Summing every line's debit
     * (equal to summing every credit, since a posted entry is always
     * balanced) gives the entry's total regardless of how many lines it has
     * — a 2-line entry or a 3-line one with a tax split both work the same
     * way, unlike picking a single line's amount.
     */
    private function activePostedAmount(JournalEntryService $journal, string $referenceType, string $referenceId): ?float
    {
        $entry = JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->where('source', '!=', JournalEntry::SOURCE_REVERSAL)
            ->with('lines')
            ->first();

        if (! $entry) {
            return null;
        }

        return (float) $entry->lines->sum(fn ($line) => (float) $line->debit);
    }

    private function fixCodAmount(Order $order): void
    {
        $correct = (float) ($order->total_outstanding ?? $order->total);

        if ($order->cod_amount !== null && abs((float) $order->cod_amount - $correct) > 0.01) {
            $old = (float) $order->cod_amount;
            $order->update(['cod_amount' => $correct]);
            $this->command?->info("cod_amount corrected: {$old} -> {$correct}");
        } else {
            $this->command?->info('cod_amount already correct — left alone.');
        }
    }

    /**
     * Nets every COD-collected credit and prior adjustment already posted
     * for this order against what should have been collected, and posts a
     * single adjustment for the difference — rather than assuming this is
     * the first correction, so re-running this after an earlier partial fix
     * (e.g. the local one already applied) posts nothing further.
     */
    private function fixRiderWallet(Order $order): void
    {
        if (! $order->rider_id || ! $order->cod_collected) {
            $this->command?->info('No rider / not COD-collected — nothing to adjust in the wallet.');

            return;
        }

        $collected = (float) RiderWalletTransaction::where('reference_type', 'orders')
            ->where('reference_id', $order->id)
            ->whereIn('transaction_type', [RiderWalletTransaction::TYPE_COD_COLLECTED, RiderWalletTransaction::TYPE_ADJUSTMENT])
            ->sum('amount');

        $correct = (float) ($order->total_outstanding ?? $order->total);
        $delta = round($correct - $collected, 2);

        if (abs($delta) <= 0.01) {
            $this->command?->info('Rider wallet already correct for this order — left alone.');

            return;
        }

        $rider = RiderProfile::find($order->rider_id);

        if (! $rider) {
            $this->command?->error('Rider not found — skipping wallet adjustment.');

            return;
        }

        DB::transaction(function () use ($rider, $order, $delta) {
            app(RiderWalletService::class)->postTransaction(
                rider: $rider,
                transactionType: RiderWalletTransaction::TYPE_ADJUSTMENT,
                amount: $delta,
                referenceType: 'orders',
                referenceId: $order->id,
                notes: "Correction: order {$order->shopify_order_number} edited in Shopify after delivery — COD collectible adjusted by {$delta}.",
            );
        });

        $this->command?->info("Rider wallet adjusted by {$delta}.");
    }
}
