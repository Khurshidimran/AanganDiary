<?php

namespace App\Http\Controllers;

use App\Models\ShopifyWebhookEvent;
use App\Services\Shopify\ShopifyOrderSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ShopifyWebhookController extends Controller
{
    public function order(Request $request, ShopifyOrderSyncService $service): JsonResponse
    {
        $webhookId = $request->header('X-Shopify-Webhook-Id');
        $topic = $request->header('X-Shopify-Topic', 'orders/unknown');

        abort_if(blank($webhookId), 400, 'Missing X-Shopify-Webhook-Id header.');

        // Idempotent: Shopify retries webhooks on timeout/non-2xx, and may redeliver
        // the same event more than once even on success. Never process twice.
        if (ShopifyWebhookEvent::where('shopify_webhook_id', $webhookId)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        $payload = $request->json()->all();

        $event = ShopifyWebhookEvent::create([
            'shopify_webhook_id' => $webhookId,
            'topic' => $topic,
            'payload' => $payload,
            'status' => ShopifyWebhookEvent::STATUS_RECEIVED,
        ]);

        try {
            $service->sync($payload);

            $event->update([
                'status' => ShopifyWebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => ShopifyWebhookEvent::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            report($e);
        }

        // Always 200 — Shopify will keep retrying non-2xx responses, and the
        // failure is already captured in the event log for follow-up.
        return response()->json(['status' => 'received']);
    }
}
