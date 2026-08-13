<?php

namespace App\Console\Commands\Shopify;

use App\Services\Shopify\ShopifyWebhookRegistrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('shopify:register-webhooks')]
#[Description('Register this app\'s order webhook subscriptions with Shopify (safe to re-run — skips topics already registered).')]
class RegisterWebhooksCommand extends Command
{
    public function handle(ShopifyWebhookRegistrationService $service): int
    {
        $results = $service->register();

        $this->table(
            ['Topic', 'Result'],
            collect($results)->map(fn ($result, $topic) => [$topic, $result])->values(),
        );

        $failed = collect($results)->filter(fn ($result) => str_starts_with($result, 'failed'));

        return $failed->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
