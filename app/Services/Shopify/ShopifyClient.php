<?php

namespace App\Services\Shopify;

use App\Exceptions\ShopifyNotConfiguredException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Shopify Admin REST API. Every Shopify HTTP call in
 * the app goes through this class — nothing else should build Shopify URLs
 * or attach the access token directly (see project rule: no scattered API calls).
 */
class ShopifyClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.shopify.shop_domain')) && filled(config('services.shopify.access_token'));
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request()->get($this->url($endpoint), $query)->throw()->json();
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request()->post($this->url($endpoint), $data)->throw()->json();
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request()->put($this->url($endpoint), $data)->throw()->json();
    }

    private function request(): PendingRequest
    {
        $this->ensureConfigured();

        return Http::withHeaders([
            'X-Shopify-Access-Token' => config('services.shopify.access_token'),
            'Content-Type' => 'application/json',
        ]);
    }

    private function url(string $endpoint): string
    {
        $shop = config('services.shopify.shop_domain');
        $version = config('services.shopify.api_version');
        $endpoint = ltrim($endpoint, '/');

        return "https://{$shop}/admin/api/{$version}/{$endpoint}";
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new ShopifyNotConfiguredException(
                'Shopify is not configured. Set SHOPIFY_SHOP_DOMAIN and SHOPIFY_ACCESS_TOKEN in .env first.',
            );
        }
    }
}
