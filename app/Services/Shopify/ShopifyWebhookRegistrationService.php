<?php

namespace App\Services\Shopify;

use Throwable;

/**
 * Registers this app's order webhook subscriptions with Shopify, pointed at
 * this app's own webhook routes. Shopify never sends a webhook for a topic
 * unless a subscription exists for it — this is the one-time (or re-run
 * after a domain change) setup step that creates those subscriptions.
 */
class ShopifyWebhookRegistrationService
{
    private const TOPICS = [
        'orders/create' => 'webhooks.shopify.orders.create',
        'orders/updated' => 'webhooks.shopify.orders.updated',
        'orders/cancelled' => 'webhooks.shopify.orders.cancelled',
        'orders/fulfilled' => 'webhooks.shopify.orders.fulfilled',
    ];

    public function __construct(private readonly ShopifyClient $client)
    {
    }

    /**
     * @return array<string, string> topic => 'created' | 'already_registered' | 'failed: <message>'
     */
    public function register(): array
    {
        $existing = collect($this->client->get('webhooks.json')['webhooks'] ?? []);

        $results = [];

        foreach (self::TOPICS as $topic => $routeName) {
            $address = route($routeName);

            $alreadyRegistered = $existing->contains(
                fn (array $webhook) => $webhook['topic'] === $topic && $webhook['address'] === $address
            );

            if ($alreadyRegistered) {
                $results[$topic] = 'already_registered';

                continue;
            }

            try {
                $this->client->post('webhooks.json', [
                    'webhook' => [
                        'topic' => $topic,
                        'address' => $address,
                        'format' => 'json',
                    ],
                ]);
                $results[$topic] = 'created';
            } catch (Throwable $e) {
                $results[$topic] = "failed: {$e->getMessage()}";
            }
        }

        return $results;
    }
}
