<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'action', 'module', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address'])]
class AuditLog extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return [
            'old_values' => 'json',
            'new_values' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable line for the order trail side card. Only covers the
     * 'orders' module actions logged by OrderController/DispatchController/
     * ShopifyOrderSyncService — unrecognized actions fall back to a
     * headline-cased version of the raw action so nothing renders blank.
     */
    public function describe(): string
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $fromShopify = ($old['source'] ?? $new['source'] ?? null) === 'shopify';

        return match ($this->action) {
            'created' => $fromShopify ? 'Order created (synced from Shopify)' : 'Order created manually',
            'confirmed' => $fromShopify ? 'Order auto-confirmed on Shopify sync' : 'Order confirmed',
            'cancelled' => $fromShopify ? 'Order cancelled on Shopify' : 'Order cancelled',
            'assigned' => 'Assigned to '.($new['rider_name'] ?? 'a rider'),
            'unassigned' => 'Unassigned from '.($old['rider'] ?? 'rider').' — back to pending',
            'instructions_updated' => filled($new['rider_instructions'] ?? null)
                ? 'Rider instructions updated: "'.str($new['rider_instructions'])->limit(80).'"'
                : 'Rider instructions cleared',
            'picked_up' => 'Marked as picked up',
            'out_for_delivery' => 'Marked as out for delivery',
            'delivered' => 'Marked as delivered',
            'delivery_failed' => 'Delivery failed — '.($new['reason'] ?? 'no reason given'),
            'returned' => 'Marked as returned — stock released back to inventory',
            default => str($this->action)->headline(),
        };
    }
}
