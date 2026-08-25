<?php

namespace App\Services\Shopify;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Models\Channel;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShopifySyncLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\OrderFulfillmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    private ?Channel $shopifyChannel = null;

    public function __construct(
        private readonly ShopifyClient $client,
        private readonly OrderFulfillmentService $fulfillment,
        private readonly AuditLogService $auditLog,
    ) {
    }

    /**
     * Looked up once per sync batch (not per order) — the Shopify channel is
     * seeded/system-protected (see ChannelSeeder), so this only returns null
     * if a deployment somehow never ran that seeder.
     */
    private function shopifyChannel(): ?Channel
    {
        return $this->shopifyChannel ??= Channel::where('code', 'shopify')->first();
    }

    /**
     * Maps Shopify's own order-origin identifier (source_name — e.g. 'web'
     * for Online Store, 'shopify_draft_order' for Draft Orders) to one of
     * our seeded Channel rows (see ChannelSeeder). Falls back to the generic
     * "Shopify" channel for a source_name that isn't recognized yet (a new
     * sales channel, or a third-party app whose identifier hasn't been
     * mapped) — never null as long as the seeder has run.
     */
    public function resolveChannel(?string $sourceName): ?Channel
    {
        if ($sourceName) {
            $matched = Channel::where('source_name', $sourceName)->first();

            if ($matched) {
                return $matched;
            }
        }

        return $this->shopifyChannel();
    }

    /**
     * An order already sitting on a specific, correctly-resolved channel (or
     * one a staff member deliberately corrected) is left alone — only an
     * order still on the generic "Shopify" fallback (or with no channel at
     * all) gets (re-)resolved from the current source_name. This means an
     * order synced before its real channel was mapped self-heals the next
     * time Shopify sends a webhook for it or it's re-pulled, with no
     * separate backfill step required.
     */
    private function resolveChannelId(Order $order, ?string $sourceName): ?string
    {
        if ($order->channel_id && $order->channel_id !== $this->shopifyChannel()?->id) {
            return $order->channel_id;
        }

        return $this->resolveChannel($sourceName)?->id ?? $order->channel_id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sync(array $payload): Order
    {
        $isNew = false;

        $order = DB::transaction(function () use ($payload, &$isNew) {
            $order = Order::withTrashed()->firstOrNew(['shopify_order_id' => (string) $payload['id']]);
            $isNew = ! $order->exists;
            $wasCancelled = $order->getOriginal('order_status') === Order::ORDER_STATUS_CANCELLED;

            $customer = $this->resolveCustomer($payload);

            $order->fill([
                'shopify_order_number' => $payload['name'] ?? ($payload['order_number'] ?? null),
                'channel_id' => $this->resolveChannelId($order, $payload['source_name'] ?? null),
                'shopify_source_name' => $payload['source_name'] ?? null,
                'customer_id' => $customer?->id,
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
                'discount_total' => $payload['total_discounts'] ?? 0,
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
                // Shopify orders are always prepaid-at-checkout or COD-on-
                // delivery in this business — never "buy now, pay the store
                // later" credit terms, so they're never a Receivables Aging
                // candidate.
                $order->payment_type = Order::PAYMENT_TYPE_CASH;
            }

            $order->save();

            if ($customer) {
                $this->syncCustomerAddress($customer, $payload['shipping_address'] ?? null);
            }

            if ($isNew) {
                $this->auditLog->log('created', 'orders', $order, null, ['shopify_order_number' => $order->shopify_order_number, 'source' => 'shopify']);
            } elseif ($order->order_status === Order::ORDER_STATUS_CANCELLED && ! $wasCancelled) {
                $this->auditLog->log('cancelled', 'orders', $order, null, ['order_status' => $order->order_status, 'source' => 'shopify']);
            }

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

        // Deliberately outside the transaction above: allocateStock() manages
        // its own transaction and can fail on insufficient stock, and a
        // failure here must never undo the order sync itself — Shopify
        // won't redeliver a webhook just because we couldn't reserve stock.
        // A newly-arrived, non-cancelled order auto-confirms; if allocation
        // fails it's simply left pending, same as the pre-existing manual
        // confirm flow (OrderController::confirm), for staff to retry later.
        if ($isNew && $order->order_status === Order::ORDER_STATUS_PENDING) {
            $this->tryAutoConfirm($order);
        }

        return $order;
    }

    /**
     * Cancels the order on Shopify's side too, when it's cancelled here —
     * restocks Shopify's inventory (item becomes purchasable again on the
     * storefront) and lets Shopify send its own cancellation email. Throws
     * on failure (ShopifyClient::post() already ->throw()s on a non-2xx
     * response) — the caller is expected to block the local cancellation if
     * this fails, so the two systems never drift out of sync.
     */
    public function cancelInShopify(Order $order): void
    {
        $this->client->post("orders/{$order->shopify_order_id}/cancel.json", [
            'restock' => true,
            'email' => true,
        ]);
    }

    private function tryAutoConfirm(Order $order): void
    {
        // Confirmation is unconditional — a stock shortage or missing
        // warehouse config no longer blocks it, only stops the allocation
        // itself. Inventory accuracy is a separate concern from getting the
        // order into the dispatch pipeline.
        try {
            $this->fulfillment->allocateStock($order);
        } catch (InsufficientStockException|WarehouseNotConfiguredException) {
            // Stock not allocated — order still confirms below regardless.
        }

        $order->update(['order_status' => Order::ORDER_STATUS_CONFIRMED]);
        $this->auditLog->log('confirmed', 'orders', $order, null, ['order_status' => $order->order_status, 'source' => 'shopify']);
    }

    /**
     * Manual catch-up sync for when webhooks were missed (e.g. server downtime) —
     * pulls orders directly from Shopify's Admin API and runs each one through the
     * same sync() upsert used by the webhook path. Because sync() keys on
     * shopify_order_id via firstOrNew, re-pulling an order that's already in the
     * database just updates it in place rather than creating a duplicate — the
     * same guarantee the webhook path already relies on, not new dedup logic.
     *
     * status=any is used (Shopify's default excludes cancelled/closed orders) so
     * a cancellation that happened during the downtime is also caught.
     */
    public function pullFromShopify(?Carbon $since = null, ?User $triggeredBy = null): ShopifySyncLog
    {
        $log = ShopifySyncLog::create([
            'sync_type' => ShopifySyncLog::TYPE_ORDERS_PULL,
            'status' => ShopifySyncLog::STATUS_RUNNING,
            'started_at' => now(),
            'triggered_by' => $triggeredBy?->id,
        ]);

        $processed = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            $response = $this->client->get('orders.json', array_filter([
                'status' => 'any',
                'limit' => 250,
                'updated_at_min' => $since?->toIso8601String(),
            ]));

            foreach ($response['orders'] ?? [] as $orderPayload) {
                $processed++;

                try {
                    $existed = Order::withTrashed()->where('shopify_order_id', (string) $orderPayload['id'])->exists();
                    $this->sync($orderPayload);
                    $existed ? $updated++ : $created++;
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = "Order {$orderPayload['name']}: {$e->getMessage()}";
                }
            }

            $log->update([
                'status' => ShopifySyncLog::STATUS_COMPLETED,
                'items_processed' => $processed,
                'items_created' => $created,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'error_summary' => $errors ? implode("\n", $errors) : null,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $log->update([
                'status' => ShopifySyncLog::STATUS_FAILED,
                'items_processed' => $processed,
                'items_created' => $created,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'error_summary' => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }

        return $log->fresh();
    }

    /**
     * Matches the order to a Customer record by phone (normalized to the
     * last 10 digits, so "+92 340 0009454", "03400009454" and
     * "923400009454" all resolve to the same customer), falling back to
     * email if no phone match is found. Creates a new Customer when
     * neither matches — returns null only when the order carries neither a
     * phone nor a name to create one with (customer_id already tolerates
     * null; this just leaves it unset, same as every order before this).
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveCustomer(array $payload): ?Customer
    {
        $phone = $payload['phone']
            ?? ($payload['customer']['phone'] ?? null)
            ?? ($payload['billing_address']['phone'] ?? null)
            ?? ($payload['shipping_address']['phone'] ?? null);

        $normalizedPhone = $phone ? substr(preg_replace('/\D+/', '', $phone), -10) : null;

        if ($normalizedPhone) {
            $existing = Customer::whereRaw("RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 10) = ?", [$normalizedPhone])->first();

            if ($existing) {
                return $existing;
            }
        }

        $email = $payload['email'] ?? ($payload['contact_email'] ?? null);

        if ($email) {
            $existing = Customer::where('email', $email)->first();

            if ($existing) {
                return $existing;
            }
        }

        // customers.phone is required at the database level — an order with
        // neither a phone nor a name to fall back on can't produce a valid
        // Customer, so it's left unlinked rather than guessing.
        if (! $normalizedPhone && ! $phone) {
            return null;
        }

        return Customer::create([
            'name' => $this->customerName($payload) ?? 'Unknown Customer',
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    /**
     * Keeps a customer's saved address book in sync with Shopify's geocoded
     * coordinates. Matches an existing address by street + city (so a
     * repeat order to the same place refreshes its pin rather than piling
     * up duplicates); creates a new saved address otherwise, mirroring the
     * same is_default convention CustomerAddressController::store() uses.
     *
     * @param  array<string, mixed>|null  $shippingAddress
     */
    private function syncCustomerAddress(Customer $customer, ?array $shippingAddress): void
    {
        $address1 = trim((string) ($shippingAddress['address1'] ?? ''));

        if ($address1 === '') {
            return;
        }

        $city = $shippingAddress['city'] ?? 'Lahore';

        $existing = CustomerAddress::where('customer_id', $customer->id)
            ->whereRaw('LOWER(address1) = ?', [mb_strtolower($address1)])
            ->whereRaw('LOWER(city) = ?', [mb_strtolower((string) $city)])
            ->first();

        if ($existing) {
            $existing->update([
                'latitude' => $shippingAddress['latitude'] ?? $existing->latitude,
                'longitude' => $shippingAddress['longitude'] ?? $existing->longitude,
            ]);

            return;
        }

        $customer->addresses()->create([
            'address1' => $address1,
            'address2' => $shippingAddress['address2'] ?? null,
            'city' => $city,
            'country' => $shippingAddress['country'] ?? 'Pakistan',
            'phone' => $shippingAddress['phone'] ?? null,
            'latitude' => $shippingAddress['latitude'] ?? null,
            'longitude' => $shippingAddress['longitude'] ?? null,
            'is_default' => $customer->addresses()->count() === 0,
        ]);
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
