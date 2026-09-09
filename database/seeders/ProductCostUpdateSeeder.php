<?php

namespace Database\Seeders;

use App\Models\ProductVariant;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Seeder;

/**
 * One-off cost correction from the client's real "Products Cost" list —
 * sets ProductVariant.purchase_price for the matched SKUs, and refreshes
 * unit_cost on any not-yet-received PurchaseOrderItem line for those same
 * variants, so an already-created but still-unposted purchase order picks
 * up the corrected cost too (a line that already has stock received against
 * it is left untouched — that's a completed transaction, not something to
 * silently rewrite).
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=ProductCostUpdateSeeder
 * Safe to re-run: every write here is an UPDATE keyed by SKU/variant, never
 * a create, so running it again just re-applies the same values.
 *
 * Two rows from the client's list were deliberately left out of COSTS below:
 * - "100% Mozzarella Shredded Cheese dice 2kg" (Rs. 2300) — no matching
 *   product exists anywhere in the catalog (checked trashed rows too, and
 *   both existing Mozzarella Shredded Cheese variants — 1kg/2kg — are
 *   already covered separately below); nothing to update.
 * "Jalapeno Cheddar Cheese Block" (JCCB500G) IS included below despite a
 * three-way size-label mismatch (product name says 400g, SKU says 500g,
 * the client's list says 200g) — client confirmed to apply it anyway.
 */
class ProductCostUpdateSeeder extends Seeder
{
    /**
     * [sku => cost]
     */
    private const COSTS = [
        'S70301000G-1' => 1050.00, // 70/30 Shredded Cheese 1kg
        'S70302000G' => 2100.00,   // 70/30 Shredded Cheese 2kg
        'S50501000G' => 950.00,    // 50/50 Shredded Cheese 1kg
        'S50502000G' => 1900.00,   // 50/50 Shredded Cheese 2kg
        'WCCS1000G' => 1000.00,    // Premium White Cheddar Cheese slices 1kg
        'BS1000G' => 1000.00,      // Yellow Burger Slices 1kg
        'MSC1000G' => 1100.00,     // 100% Mozzarella Shredded Cheese 1kg
        'MSC2000G' => 2200.00,     // 100% Mozzarella Shredded Cheese 2kg
        'CCB1000G' => 765.00,      // Cheddar Cheese Block 1kg
        'CCB2000G' => 1530.00,     // Cheddar Cheese Block 2kg
        'MC1000G' => 1015.00,      // Mozzarella Cheese Block 1kg
        'MC2000G' => 2030.00,      // Mozzarella Cheese Block 2kg
        'DG1000G' => 2100.00,      // Pure Desi Ghee 1kg
        'CCS200G' => 260.00,       // White Cheddar Cheese slices 200gm
        'JCCB500G' => 300.00,      // Jalapeno Cheddar Cheese Block
    ];

    public function run(): void
    {
        $updatedVariants = 0;
        $updatedPoLines = 0;
        $notFound = [];

        foreach (self::COSTS as $sku => $cost) {
            $variant = ProductVariant::where('sku', $sku)->first();

            if (! $variant) {
                $notFound[] = $sku;

                continue;
            }

            $variant->update(['purchase_price' => $cost]);
            $updatedVariants++;

            $updatedPoLines += PurchaseOrderItem::where('product_variant_id', $variant->id)
                ->where('quantity_received', 0)
                ->update(['unit_cost' => $cost]);
        }

        $this->command?->info("Updated purchase_price on {$updatedVariants} product variant(s), unit_cost on {$updatedPoLines} not-yet-received purchase order line(s).");

        if ($notFound !== []) {
            $this->command?->warn('SKUs not found (skipped): '.implode(', ', $notFound));
        }
    }
}
