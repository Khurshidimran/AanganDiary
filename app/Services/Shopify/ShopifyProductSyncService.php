<?php

namespace App\Services\Shopify;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopifySyncLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports products and variants from Shopify into the local catalog, matching
 * existing variants by SKU and creating new Product/ProductVariant records for
 * anything not already present locally. This establishes the shopify_product_id /
 * shopify_variant_id / shopify_inventory_item_id mapping everything else relies on.
 *
 * Fetches a single page (up to 250 products) for now — add Link-header cursor
 * pagination here if the catalog grows past that.
 */
class ShopifyProductSyncService
{
    public function __construct(private readonly ShopifyClient $client)
    {
    }

    public function import(?User $triggeredBy = null): ShopifySyncLog
    {
        $response = $this->client->get('products.json', ['limit' => 250]);

        return $this->importFromPayload($response, $triggeredBy);
    }

    /**
     * Runs the same sync logic as import(), but against an already-fetched
     * payload shaped like the products.json response (`{"products": [...]}`).
     * Useful for importing a Shopify data export directly when live API access
     * isn't available.
     *
     * @param  array<string, mixed>  $payload
     */
    public function importFromPayload(array $payload, ?User $triggeredBy = null): ShopifySyncLog
    {
        $this->logRawPayload($payload);

        $log = ShopifySyncLog::create([
            'sync_type' => ShopifySyncLog::TYPE_PRODUCTS_IMPORT,
            'status' => ShopifySyncLog::STATUS_RUNNING,
            'started_at' => now(),
            'triggered_by' => $triggeredBy?->id,
        ]);

        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            foreach ($payload['products'] ?? [] as $shopifyProduct) {
                $processed++;

                try {
                    $outcome = $this->syncProduct($shopifyProduct);
                    $created += $outcome === 'created' ? 1 : 0;
                    $updated += $outcome === 'updated' ? 1 : 0;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = "{$shopifyProduct['title']}: {$e->getMessage()}";
                }
            }

            $log->update([
                'status' => ShopifySyncLog::STATUS_COMPLETED,
                'items_processed' => $processed,
                'items_created' => $created,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'error_summary' => $errors ? implode("\n", $errors) : null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => ShopifySyncLog::STATUS_FAILED,
                'items_processed' => $processed,
                'items_created' => $created,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'error_summary' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }

        return $log->fresh();
    }

    /**
     * Writes the raw Shopify response to storage/logs/shopify/products-sync-{date}.txt
     * so a mismatch between what Shopify sent and what got imported can be
     * diagnosed after the fact — one file per day, appended on every sync run
     * that day (both live API pulls and file-based imports go through here).
     *
     * @param  array<string, mixed>  $payload
     */
    private function logRawPayload(array $payload): void
    {
        $directory = storage_path('logs/shopify');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/products-sync-'.now()->format('Y-m-d').'.txt';

        $entry = '=== '.now()->toDateTimeString()." ===\n"
            .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ."\n\n";

        File::append($path, $entry);
    }

    /**
     * @param  array<string, mixed>  $shopifyProduct
     */
    private function syncProduct(array $shopifyProduct): string
    {
        return DB::transaction(function () use ($shopifyProduct) {
            $variants = $shopifyProduct['variants'] ?? [];
            $anyCreated = false;
            $anyUpdated = false;

            // Shopify has no structured "bundle" concept in this API — a product
            // is treated as a bundle/kit here only when every one of its variants
            // has no SKU (real inventory-tracked variants always carry a SKU by
            // convention in this catalog). Bundle composition itself (which SKUs
            // it's made of) can't be inferred from the API and must be entered
            // manually via the product edit screen afterward.
            $isBundle = $variants !== [] && collect($variants)->every(
                fn (array $v) => trim((string) ($v['sku'] ?? '')) === '',
            );

            $product = Product::whereHas(
                'variants',
                fn ($q) => $q->where('shopify_product_id', (string) $shopifyProduct['id']),
            )->first();

            foreach ($variants as $shopifyVariant) {
                $rawSku = trim((string) ($shopifyVariant['sku'] ?? ''));
                $sku = $rawSku !== '' ? $rawSku : 'BUNDLE-'.$shopifyVariant['id'];

                $existingVariant = ProductVariant::where('sku', $sku)->first();

                if ($existingVariant) {
                    $existingVariant->update([
                        'shopify_product_id' => (string) $shopifyProduct['id'],
                        'shopify_variant_id' => (string) $shopifyVariant['id'],
                        'shopify_inventory_item_id' => isset($shopifyVariant['inventory_item_id'])
                            ? (string) $shopifyVariant['inventory_item_id']
                            : null,
                        // These mirror Shopify's current values on every sync,
                        // not just at creation — previously only the Shopify
                        // ID linkage fields were refreshed here, so a price
                        // change made on Shopify was silently never reflected
                        // locally after the variant's first sync.
                        'name' => $shopifyVariant['title'] ?? $sku,
                        'barcode' => $shopifyVariant['barcode'] ?? null,
                        'sale_price' => $shopifyVariant['price'] ?? 0,
                        'compare_at_price' => $shopifyVariant['compare_at_price'] ?? null,
                    ]);
                    $anyUpdated = true;

                    continue;
                }

                $product ??= $this->createProductFromShopify($shopifyProduct, $isBundle);

                $product->variants()->create([
                    'name' => $shopifyVariant['title'] ?? $sku,
                    'sku' => $sku,
                    'barcode' => $shopifyVariant['barcode'] ?? null,
                    'shopify_product_id' => (string) $shopifyProduct['id'],
                    'shopify_variant_id' => (string) $shopifyVariant['id'],
                    'shopify_inventory_item_id' => isset($shopifyVariant['inventory_item_id'])
                        ? (string) $shopifyVariant['inventory_item_id']
                        : null,
                    'purchase_price' => 0,
                    'sale_price' => $shopifyVariant['price'] ?? 0,
                    'compare_at_price' => $shopifyVariant['compare_at_price'] ?? null,
                    'unit_id' => $this->unitIdForVariant($shopifyVariant),
                    'pack_size' => $this->packSizeForVariant($shopifyVariant),
                    'is_active' => true,
                ]);
                $anyCreated = true;
            }

            return $anyCreated ? 'created' : ($anyUpdated ? 'updated' : 'skipped');
        });
    }

    /**
     * @param  array<string, mixed>  $shopifyProduct
     */
    private function createProductFromShopify(array $shopifyProduct, bool $isBundle): Product
    {
        $brand = null;

        if (filled($shopifyProduct['vendor'] ?? null)) {
            $brand = Brand::firstOrCreate(
                ['slug' => Str::slug($shopifyProduct['vendor'])],
                ['name' => $shopifyProduct['vendor'], 'status' => 'active'],
            );
        }

        $product = Product::create([
            'brand_id' => $brand?->id,
            'name' => $shopifyProduct['title'],
            'slug' => $this->uniqueSlug($shopifyProduct['title']),
            'description' => strip_tags($shopifyProduct['body_html'] ?? '') ?: null,
            'tags' => $shopifyProduct['tags'] ?? null,
            'is_bundle' => $isBundle,
            'supply_type' => 'purchased',
            'track_inventory' => ! $isBundle,
            'status' => ($shopifyProduct['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
        ]);

        $this->syncProductImages($product, $shopifyProduct);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $shopifyProduct
     */
    private function syncProductImages(Product $product, array $shopifyProduct): void
    {
        $images = collect($shopifyProduct['images'] ?? [])
            ->filter(fn (array $image) => filled($image['src'] ?? null))
            ->sortBy('position')
            ->values();

        foreach ($images as $index => $image) {
            $product->images()->create([
                'url' => $image['src'],
                'sort_order' => $index,
            ]);
        }

        if ($primary = $images->first()['src'] ?? null) {
            $product->update(['image' => $primary]);
        }
    }

    private function fallbackUnitId(): ?string
    {
        return Unit::where('short_code', 'pcs')->value('id');
    }

    /**
     * Shopify's weight_unit is the only reliable size signal on the variant
     * (grams is always normalized in grams regardless of weight_unit, so it's
     * not usable directly against our per-type unit table). Falls back to
     * "pcs" for weightless variants (bundles/deals with no shipping weight).
     *
     * @param  array<string, mixed>  $shopifyVariant
     */
    private function unitIdForVariant(array $shopifyVariant): ?string
    {
        $shortCode = match (strtolower((string) ($shopifyVariant['weight_unit'] ?? ''))) {
            'kg' => 'kg',
            'g' => 'g',
            'l' => 'L',
            'ml' => 'ml',
            default => null,
        };

        $unitId = $shortCode ? Unit::where('short_code', $shortCode)->value('id') : null;

        return $unitId ?? $this->fallbackUnitId();
    }

    /**
     * @param  array<string, mixed>  $shopifyVariant
     */
    private function packSizeForVariant(array $shopifyVariant): float
    {
        $weight = (float) ($shopifyVariant['weight'] ?? 0);

        return $weight > 0 ? $weight : 1;
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
