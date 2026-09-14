<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Links order_items sold as "Cheesy Slice Deal 2" (Shopify variant id
 * 53405569024367) to their ProductVariant, for orders placed before that
 * variant existed in our catalog here.
 *
 * Found via the Sept 1-30 Sales-vs-COGS audit: this variant was only
 * created locally on 2026-09-08 23:42, so every order for it placed before
 * then synced with product_variant_id left null (matchVariant() correctly
 * found nothing at the time) — order #9912 (placed 2026-09-12, after the
 * variant existed) matched fine, confirming the timing. This is an exact
 * ID match against shopify_variant_id already stored on each order_item —
 * not a fuzzy name guess — so it's safe to apply without per-order review.
 *
 * Only fixes the link; run BackfillMissingSeptemberCogsSeeder afterward to
 * actually post the now-costable COGS entries for these orders (that
 * seeder already checks status idempotently, so it's safe to re-run even
 * if it's already been run once for the other September gaps).
 *
 * Idempotent — only touches rows where product_variant_id is still null.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=LinkOrphanedCheesySliceDeal2OrderItemsSeeder --force
 */
class LinkOrphanedCheesySliceDeal2OrderItemsSeeder extends Seeder
{
    private const SHOPIFY_VARIANT_ID = '53405569024367';

    public function run(): void
    {
        $variant = ProductVariant::where('shopify_variant_id', self::SHOPIFY_VARIANT_ID)->first();

        if (! $variant) {
            $this->command?->error('Variant '.self::SHOPIFY_VARIANT_ID.' (Cheesy Slice Deal 2) not found — nothing to link.');

            return;
        }

        $updated = OrderItem::where('shopify_variant_id', self::SHOPIFY_VARIANT_ID)
            ->whereNull('product_variant_id')
            ->update(['product_variant_id' => $variant->id, 'sku' => $variant->sku]);

        $this->command?->info("Linked {$updated} order item(s) to {$variant->sku}. Run BackfillMissingSeptemberCogsSeeder next to post their COGS.");
    }
}
