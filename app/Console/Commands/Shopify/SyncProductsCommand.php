<?php

namespace App\Console\Commands\Shopify;

use App\Models\ShopifySyncLog;
use App\Services\Shopify\ShopifyProductSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shopify:sync-products')]
#[Description('Import products and variants from Shopify, matching existing local variants by SKU.')]
class SyncProductsCommand extends Command
{
    public function handle(ShopifyProductSyncService $service): int
    {
        $log = $service->import();

        $this->info("Sync finished with status: {$log->status}");
        $this->table(
            ['Processed', 'Created', 'Updated', 'Failed'],
            [[$log->items_processed, $log->items_created, $log->items_updated, $log->items_failed]],
        );

        if ($log->error_summary) {
            $this->error($log->error_summary);
        }

        return $log->status === ShopifySyncLog::STATUS_COMPLETED ? self::SUCCESS : self::FAILURE;
    }
}
