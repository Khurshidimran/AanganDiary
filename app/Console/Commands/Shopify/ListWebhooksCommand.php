<?php

namespace App\Console\Commands\Shopify;

use App\Services\Shopify\ShopifyClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shopify:list-webhooks')]
#[Description('List the webhook subscriptions Shopify actually has on file for this app, for diagnosing missing orders.')]
class ListWebhooksCommand extends Command
{
    public function handle(ShopifyClient $client): int
    {
        $webhooks = $client->get('webhooks.json')['webhooks'] ?? [];

        if ($webhooks === []) {
            $this->error('Shopify has zero webhook subscriptions on file for this app. Run shopify:register-webhooks.');

            return self::FAILURE;
        }

        $this->table(
            ['Topic', 'Address', 'Created At'],
            collect($webhooks)->map(fn (array $w) => [$w['topic'], $w['address'], $w['created_at']]),
        );

        return self::SUCCESS;
    }
}
