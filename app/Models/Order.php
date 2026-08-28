<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'shopify_order_id', 'shopify_order_number', 'channel_id', 'shopify_source_name', 'customer_id', 'customer_name', 'customer_email', 'customer_phone',
    'billing_address', 'shipping_address', 'order_status', 'payment_status', 'payment_type', 'delivery_status',
    'currency', 'subtotal', 'discount_total', 'tax_total', 'shipping_total', 'total', 'total_outstanding', 'notes', 'shopify_created_at',
    'rider_id', 'route_sequence', 'assigned_at', 'scheduled_dispatch_at', 'rider_instructions',
    'picked_up_at', 'pop_photo_path', 'pop_captured_at', 'delivered_at', 'cod_amount', 'cod_collected',
    'delivery_failure_reason', 'return_reason', 'cancellation_reason', 'pod_photo_path', 'pod_signature_path', 'pod_captured_at',
    'deleted_by',
])]
class Order extends Model
{
    use HasUuid, SoftDeletes;

    public const ORDER_STATUS_PENDING = 'pending';
    public const ORDER_STATUS_CONFIRMED = 'confirmed';
    public const ORDER_STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const PAYMENT_TYPE_CASH = 'cash';
    public const PAYMENT_TYPE_CREDIT = 'credit';

    public const DELIVERY_STATUS_PENDING = 'pending';
    public const DELIVERY_STATUS_ASSIGNED = 'assigned';
    public const DELIVERY_STATUS_PICKED_UP = 'picked_up';
    public const DELIVERY_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERY_STATUS_DELIVERED = 'delivered';
    public const DELIVERY_STATUS_FAILED = 'failed';
    public const DELIVERY_STATUS_RETURNED = 'returned';

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'total' => 'decimal:2',
            'total_outstanding' => 'decimal:2',
            'shopify_created_at' => 'datetime',
            'assigned_at' => 'datetime',
            'scheduled_dispatch_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'pop_captured_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cod_amount' => 'decimal:2',
            'cod_collected' => 'boolean',
            'pod_captured_at' => 'datetime',
            'route_sequence' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class, 'rider_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    /**
     * The A-Z activity trail (created, every status change, rider
     * assignment, delivery outcome) shown on the order's Trail side card —
     * oldest first, so it reads as a chronological story.
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class)->orderBy('attempt_number');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->with('user')->oldest();
    }

    /**
     * Manually-entered orders (phone/WhatsApp/etc.) get a synthetic
     * shopify_order_id (that column is NOT NULL + UNIQUE with no default) —
     * this is how the rest of the app tells the two apart, e.g. to skip
     * calling Shopify's cancel API for an order Shopify never knew about.
     */
    public function isFromShopify(): bool
    {
        return ! str_starts_with((string) $this->shopify_order_id, 'local-');
    }

    /**
     * Prefers the delivery address over billing — reports/manifests care
     * where the parcel is going, not where the customer is billed.
     */
    public function formattedAddress(): ?string
    {
        $address = $this->shipping_address ?? $this->billing_address;

        if (! $address) {
            return null;
        }

        $parts = array_filter([
            $address['address1'] ?? null,
            $address['address2'] ?? null,
            $address['city'] ?? null,
            $address['country'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * "1x Cheese, 2x Milk" — assumes items are already eager-loaded to
     * avoid an N+1 when called across a report's worth of orders.
     */
    public function itemsSummary(): string
    {
        return $this->items
            ->map(fn (OrderItem $item) => ((int) $item->quantity).'x '.$item->product_name)
            ->implode(', ');
    }

    public function canBeConfirmed(): bool
    {
        return $this->order_status === self::ORDER_STATUS_PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->order_status, [self::ORDER_STATUS_PENDING, self::ORDER_STATUS_CONFIRMED], true);
    }

    /**
     * Deleting is an admin-only cleanup action for orders that shouldn't
     * exist (duplicates, test orders, mistakes) — not a normal business
     * outcome, which is what Cancel is for. Blocked while a rider physically
     * has the parcel (assigned/picked_up/out_for_delivery): deleting it out
     * from under an active delivery would make the order vanish from the
     * rider's app mid-run with no trace of what happened to it.
     */
    public function canBeDeleted(): bool
    {
        return ! in_array($this->delivery_status, [
            self::DELIVERY_STATUS_ASSIGNED,
            self::DELIVERY_STATUS_PICKED_UP,
            self::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        ], true);
    }

    public function canBeAssigned(): bool
    {
        // FAILED and RETURNED are both included so a dispatcher can put a
        // failed or returned delivery back into the pipeline with a
        // different rider — neither is a dead end.
        return $this->order_status === self::ORDER_STATUS_CONFIRMED
            && in_array($this->delivery_status, [
                self::DELIVERY_STATUS_PENDING,
                self::DELIVERY_STATUS_ASSIGNED,
                self::DELIVERY_STATUS_FAILED,
                self::DELIVERY_STATUS_RETURNED,
            ], true);
    }

    /**
     * Unassigning only makes sense before pickup — once a rider has the
     * parcel in hand, reassignment happens by picking a different rider via
     * canBeAssigned() rather than reverting to unassigned.
     */
    public function canBeUnassigned(): bool
    {
        return $this->delivery_status === self::DELIVERY_STATUS_ASSIGNED && ! $this->isCancelled();
    }

    public function canBeMarkedPickedUp(): bool
    {
        return $this->delivery_status === self::DELIVERY_STATUS_ASSIGNED && ! $this->isCancelled();
    }

    public function canBeMarkedOutForDelivery(): bool
    {
        return $this->delivery_status === self::DELIVERY_STATUS_PICKED_UP && ! $this->isCancelled();
    }

    public function canBeMarkedDelivered(): bool
    {
        return $this->delivery_status === self::DELIVERY_STATUS_OUT_FOR_DELIVERY && ! $this->isCancelled();
    }

    /**
     * A rider can already be mid-delivery when Shopify cancels the order out
     * from under them (order_status is updated by the webhook regardless of
     * delivery progress) — this stops staff from advancing it further.
     */
    public function isCancelled(): bool
    {
        return $this->order_status === self::ORDER_STATUS_CANCELLED;
    }

    public function canBeMarkedFailedOrReturned(): bool
    {
        return in_array($this->delivery_status, [
            self::DELIVERY_STATUS_ASSIGNED,
            self::DELIVERY_STATUS_PICKED_UP,
            self::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        ], true);
    }
}
