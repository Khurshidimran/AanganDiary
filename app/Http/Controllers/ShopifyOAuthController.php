<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * One-time (or re-run-when-needed) OAuth handshake that issues a fresh
 * Shopify access token — deliberately requesting *offline* access (no
 * expiry), not the online (24h) access a naive token exchange defaults
 * away from only when grant_options[]=per-user is added. This app is a
 * headless background integration (webhooks + scheduled syncs), so there's
 * no logged-in Shopify user session to tie an online token to anyway.
 *
 * This does not touch ShopifyClient or replace how the app authenticates
 * day-to-day — it only produces a token for the admin to paste into
 * SHOPIFY_ACCESS_TOKEN in .env, same as how that value has always been set.
 */
class ShopifyOAuthController extends Controller
{
    private const SCOPES = 'read_products,read_orders,write_inventory';

    public function connect(): RedirectResponse
    {
        $this->authorize('shopify.sync');

        $shop = config('services.shopify.shop_domain');
        $apiKey = config('services.shopify.api_key');

        abort_unless($shop && $apiKey, 500, 'SHOPIFY_SHOP_DOMAIN and SHOPIFY_API_KEY must be set in .env before connecting.');

        $state = Str::random(40);
        session(['shopify_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => $apiKey,
            'scope' => self::SCOPES,
            'redirect_uri' => route('shopify.callback'),
            'state' => $state,
        ]);

        return redirect("https://{$shop}/admin/oauth/authorize?{$query}");
    }

    public function callback(Request $request): View
    {
        $this->authorize('shopify.sync');

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        $expectedState = session()->pull('shopify_oauth_state');

        abort_unless($expectedState && hash_equals($expectedState, $validated['state']), 403, 'Invalid OAuth state — please try connecting again.');

        $shop = config('services.shopify.shop_domain');

        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.api_key'),
            'client_secret' => config('services.shopify.api_secret'),
            'code' => $validated['code'],
        ])->throw()->json();

        return view('shopify.oauth-callback', [
            'accessToken' => $response['access_token'] ?? null,
            'scope' => $response['scope'] ?? null,
            'isOnline' => isset($response['expires_in']),
        ]);
    }
}
