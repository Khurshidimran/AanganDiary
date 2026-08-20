<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single physical delivery attempt against an order — an order keeps one
 * permanent number but can be attempted more than once (assigned, returned,
 * reassigned to a different rider, delivered on the second try). Populated
 * in parallel by DispatchService on every transition; Order's own columns
 * (rider_id, delivery_status, assigned_at, etc.) are untouched and remain
 * the "current state" source of truth for the dispatch board — this table
 * is purely additive history, keyed per attempt rather than per order, so a
 * rider's history of an order they no longer hold is never lost.
 */
#[Fillable([
    'order_id', 'attempt_number', 'rider_id', 'trip_id', 'status',
    'assigned_at', 'picked_up_at', 'out_for_delivery_at', 'delivered_at', 'completed_at',
    'failure_reason', 'cod_amount', 'cod_collected', 'delivery_charge', 'earning_credited',
])]
class DeliveryAttempt extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cod_amount' => 'decimal:2',
            'cod_collected' => 'boolean',
            'delivery_charge' => 'decimal:2',
            'earning_credited' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class, 'rider_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(RiderTrip::class, 'trip_id');
    }
}
