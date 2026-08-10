<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the X-Shopify-Hmac-Sha256 header against the raw request body,
 * per Shopify's webhook signing scheme. Fails closed: if the webhook secret
 * isn't configured, requests are rejected rather than silently accepted.
 */
class VerifyShopifyWebhook
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.shopify.webhook_secret');
        $signature = $request->header('X-Shopify-Hmac-Sha256');

        if (blank($secret) || blank($signature)) {
            abort(401, 'Shopify webhook verification is not configured or signature is missing.');
        }

        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        if (! hash_equals($computed, $signature)) {
            abort(401, 'Invalid Shopify webhook signature.');
        }

        return $next($request);
    }
}
