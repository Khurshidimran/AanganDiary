<?php

namespace App\Console\Commands\Shopify;

use App\Models\ShopifySyncLog;
use App\Services\Shopify\ShopifyInventoryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shopify:push-inventory')]
#[Description('Push current stock levels to Shopify for every warehouse mapped to a Shopify location.')]
class PushInventoryCommand extends Command
{
    public function handle(ShopifyInventoryService $service): int
    {
        $log = $service->push();

        $this->info("Push finished with status: {$log->status}");
        $this->table(
            ['Processed', 'Updated', 'Failed'],
            [[$log->items_processed, $log->items_updated, $log->items_failed]],
        );

        if ($log->error_summary) {
            $this->error($log->error_summary);
        }

        return $log->status === ShopifySyncLog::STATUS_COMPLETED ? self::SUCCESS : self::FAILURE;
    }
}
