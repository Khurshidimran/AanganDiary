<?php

namespace App\Services\Shopify;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Upserts a local Order (+ its OrderItems) from a Shopify order webhook payload.
 *
 * Order status is ours to manage (pending/confirmed only move via local action or
 * cancellation from Shopify); delivery status is purely an internal operational
 * concern driven by dispatch/riders later — Shopify's fulfillment status never
 * overwrites it here, only the initial "pending" default on first creation.
 */
class ShopifyOrderSyncService
{
    private const FINANCIAL_STATUS_MAP = [
        'paid' => Order::PAYMENT_STATUS_PAID,
        'partially_paid' => Order::PAYMENT_STATUS_PARTIALLY_PAID,
        'refunded' => Order::PAYMENT_STATUS_REFUNDED,
        'partially_refunded' => Order::PAYMENT_STATUS_REFUNDED,
        'pending' => Order::PAYMENT_STATUS_PENDING,
        'authorized' => Order::PAYMENT_STATUS_PENDING,
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sync(array $payload): Order
    {
        return DB::transaction(function () use ($payload) {
            $order = Order::withTrashed()->firstOrNew(['shopify_order_id' => (string) $payload['id']]);
            $isNew = ! $order->exists;

            $order->fill([
                'shopify_order_number' => $payload['name'] ?? ($payload['order_number'] ?? null),
                'customer_name' => $this->customerName($payload),
                'customer_email' => $payload['email'] ?? ($payload['contact_email'] ?? null),
                // Some order sources (e.g. third-party COD form apps) leave both the
                // top-level phone and the customer record blank but still populate
                // the address — that's the number a rider actually needs to call.
                'customer_phone' => $payload['phone']
                    ?? ($payload['customer']['phone'] ?? null)
                    ?? ($payload['billing_address']['phone'] ?? null)
                    ?? ($payload['shipping_address']['phone'] ?? null),
                'billing_address' => $payload['billing_address'] ?? null,
                'shipping_address' => $payload['shipping_address'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'subtotal' => $payload['subtotal_price'] ?? 0,
                'tax_total' => $payload['total_tax'] ?? 0,
                'shipping_total' => $payload['total_shipping_price_set']['shop_money']['amount'] ?? 0,
                'total' => $payload['total_price'] ?? 0,
                // Shopify computes this precisely (accounts for deposits/partial
                // payments); falls back to the full total for older payloads that
                // don't send it, which preserves the previous all-or-nothing behavior.
                'total_outstanding' => $payload['total_outstanding'] ?? $payload['total_price'] ?? 0,
                'payment_status' => self::FINANCIAL_STATUS_MAP[$payload['financial_status'] ?? ''] ?? Order::PAYMENT_STATUS_PENDING,
                'notes' => $payload['note'] ?? null,
                'shopify_created_at' => $payload['created_at'] ?? null,
            ]);

            if (filled($payload['cancelled_at'] ?? null)) {
                $order->order_status = Order::ORDER_STATUS_CANCELLED;
            } elseif ($isNew) {
                $order->order_status = Order::ORDER_STATUS_PENDING;
                $order->delivery_status = Order::DELIVERY_STATUS_PENDING;
            }

            $order->save();

            $order->items()->delete();

            foreach ($payload['line_items'] ?? [] as $lineItem) {
                $order->items()->create([
                    'product_variant_id' => $this->matchVariant($lineItem)?->id,
                    'shopify_product_id' => isset($lineItem['product_id']) ? (string) $lineItem['product_id'] : null,
                    'shopify_variant_id' => isset($lineItem['variant_id']) ? (string) $lineItem['variant_id'] : null,
                    'sku' => $lineItem['sku'] ?? null,
                    'product_name' => $lineItem['name'] ?? ($lineItem['title'] ?? 'Unknown item'),
                    'quantity' => $lineItem['quantity'] ?? 1,
                    'unit_price' => $lineItem['price'] ?? 0,
                    'total_price' => ($lineItem['price'] ?? 0) * ($lineItem['quantity'] ?? 1),
                ]);
            }

            return $order->fresh('items');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function customerName(array $payload): ?string
    {
        $customer = $payload['customer'] ?? null;

        if (! $customer) {
            return $payload['billing_address']['name'] ?? null;
        }

        return trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? '')) ?: null;
    }

    /**
     * @param  array<string, mixed>  $lineItem
     */
    private function matchVariant(array $lineItem): ?ProductVariant
    {
        if (isset($lineItem['variant_id'])) {
            $variant = ProductVariant::where('shopify_variant_id', (string) $lineItem['variant_id'])->first();

            if ($variant) {
                return $variant;
            }
        }

        if (filled($lineItem['sku'] ?? null)) {
            return ProductVariant::where('sku', $lineItem['sku'])->first();
        }

        return null;
    }
}
