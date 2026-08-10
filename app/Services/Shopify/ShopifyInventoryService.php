<?php

namespace App\Services\Shopify;

use App\Models\ShopifySyncLog;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Throwable;

/**
 * Pushes current local stock levels to Shopify's inventory levels API, for
 * every warehouse that's been mapped to a Shopify location (Warehouse::shopify_location_id)
 * and every variant that's already been matched to Shopify (ProductVariant::shopify_inventory_item_id).
 */
class ShopifyInventoryService
{
    public function __construct(private readonly ShopifyClient $client)
    {
    }

    public function push(?User $triggeredBy = null): ShopifySyncLog
    {
        $log = ShopifySyncLog::create([
            'sync_type' => ShopifySyncLog::TYPE_INVENTORY_PUSH,
            'status' => ShopifySyncLog::STATUS_RUNNING,
            'started_at' => now(),
            'triggered_by' => $triggeredBy?->id,
        ]);

        $processed = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            $warehouses = Warehouse::whereNotNull('shopify_location_id')->get();

            foreach ($warehouses as $warehouse) {
                $balances = StockBalance::with('productVariant')
                    ->where('warehouse_id', $warehouse->id)
                    ->whereHas('productVariant', fn ($q) => $q->whereNotNull('shopify_inventory_item_id'))
                    ->get()
                    ->groupBy('product_variant_id');

                foreach ($balances as $variantBalances) {
                    $processed++;
                    $variant = $variantBalances->first()->productVariant;
                    $available = (float) $variantBalances->sum('quantity');

                    try {
                        $this->client->post('inventory_levels/set.json', [
                            'location_id' => $warehouse->shopify_location_id,
                            'inventory_item_id' => $variant->shopify_inventory_item_id,
                            'available' => $available,
                        ]);
                        $updated++;
                    } catch (Throwable $e) {
                        $failed++;
                        $errors[] = "{$variant->sku} @ {$warehouse->name}: {$e->getMessage()}";
                    }
                }
            }

            $log->update([
                'status' => ShopifySyncLog::STATUS_COMPLETED,
                'items_processed' => $processed,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'error_summary' => $errors ? implode("\n", $errors) : null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => ShopifySyncLog::STATUS_FAILED,
                'items_processed' => $processed,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'error_summary' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }

        return $log->fresh();
    }
}
