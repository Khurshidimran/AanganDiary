<?php

namespace App\Http\Controllers;

use App\Models\ShopifySyncLog;
use App\Services\Shopify\ShopifyClient;
use App\Services\Shopify\ShopifyInventoryService;
use App\Services\Shopify\ShopifyOrderSyncService;
use App\Services\Shopify\ShopifyProductSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopifyController extends Controller
{
    public function index(ShopifyClient $client): View
    {
        $this->authorize('shopify.view');

        return view('shopify.index', [
            'isConfigured' => $client->isConfigured(),
            'shopDomain' => config('services.shopify.shop_domain'),
            'syncLogs' => ShopifySyncLog::with('triggeredBy')->latest('started_at')->paginate(15),
        ]);
    }

    public function syncProducts(Request $request, ShopifyProductSyncService $service): RedirectResponse
    {
        $this->authorize('shopify.sync');

        $log = $service->import($request->user());
        $summary = "Product sync {$log->status}: {$log->items_processed} processed, {$log->items_created} created, {$log->items_updated} updated, {$log->items_failed} failed.";

        return back()->with(
            $log->status === ShopifySyncLog::STATUS_COMPLETED ? 'status' : 'error',
            $log->error_summary ? "{$summary} {$log->error_summary}" : $summary,
        );
    }

    public function pushInventory(Request $request, ShopifyInventoryService $service): RedirectResponse
    {
        $this->authorize('shopify.sync');

        $log = $service->push($request->user());
        $summary = "Inventory push {$log->status}: {$log->items_processed} processed, {$log->items_updated} updated, {$log->items_failed} failed.";

        return back()->with(
            $log->status === ShopifySyncLog::STATUS_COMPLETED ? 'status' : 'error',
            $log->error_summary ? "{$summary} {$log->error_summary}" : $summary,
        );
    }

    public function syncOrders(Request $request, ShopifyOrderSyncService $service): RedirectResponse
    {
        $this->authorize('shopify.sync');

        $validated = $request->validate(['since' => ['nullable', 'date']]);
        $since = $validated['since'] ? Carbon::parse($validated['since']) : null;

        $log = $service->pullFromShopify($since, $request->user());
        $summary = "Order sync {$log->status}: {$log->items_processed} processed, {$log->items_created} created, {$log->items_updated} updated, {$log->items_failed} failed.";

        return back()->with(
            $log->status === ShopifySyncLog::STATUS_COMPLETED ? 'status' : 'error',
            $log->error_summary ? "{$summary} {$log->error_summary}" : $summary,
        );
    }
}
