<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'warehouse_id', 'phone', 'cnic', 'vehicle_type', 'vehicle_number',
    'zone', 'per_delivery_rate', 'wallet_balance', 'status', 'fcm_token',
    'last_latitude', 'last_longitude', 'last_location_at',
    'is_online', 'is_checked_in', 'checked_in_at', 'checked_in_latitude', 'checked_in_longitude',
])]
class RiderProfile extends Model
{
    use HasUuid, HasLocalizedTimestamps, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ON_LEAVE = 'on_leave';

    /**
     * How close a rider must be to their assigned warehouse for check-in to
     * succeed — loose enough to tolerate normal phone GPS drift.
     */
    public const CHECK_IN_RADIUS_METERS = 200;

    protected function casts(): array
    {
        return [
            'per_delivery_rate' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
            'last_latitude' => 'decimal:7',
            'last_longitude' => 'decimal:7',
            'last_location_at' => 'datetime',
            'is_online' => 'boolean',
            'is_checked_in' => 'boolean',
            'checked_in_at' => 'datetime',
            'checked_in_latitude' => 'decimal:7',
            'checked_in_longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(RiderWalletTransaction::class, 'rider_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'rider_id');
    }

    public function locationPings(): HasMany
    {
        return $this->hasMany(RiderLocationPing::class, 'rider_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(RiderTrip::class, 'rider_id');
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class, 'rider_id');
    }

    /**
     * Great-circle (haversine) distance in meters from the given point to
     * this rider's assigned warehouse — null if there's no warehouse
     * assigned, or the warehouse has no coordinates set.
     */
    public function distanceToWarehouseMeters(float $latitude, float $longitude): ?float
    {
        $warehouse = $this->warehouse;

        if (! $warehouse || $warehouse->latitude === null || $warehouse->longitude === null) {
            return null;
        }

        $earthRadiusMeters = 6371000;

        $latDelta = deg2rad((float) $warehouse->latitude - $latitude);
        $lngDelta = deg2rad((float) $warehouse->longitude - $longitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad((float) $warehouse->latitude)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function isWithinCheckInRange(float $latitude, float $longitude): bool
    {
        $distance = $this->distanceToWarehouseMeters($latitude, $longitude);

        return $distance !== null && $distance <= self::CHECK_IN_RADIUS_METERS;
    }

    /**
     * A single human-facing status label for the Rider Account header —
     * checked precedence: deactivated riders always show Inactive regardless
     * of any stale online/checked-in flags; a rider actively holding a
     * picked-up/out-for-delivery order is "On Delivery" even if their app
     * hasn't reported online; otherwise checked-in beats plain online.
     */
    public function operationalStatusLabel(): string
    {
        if ($this->status === self::STATUS_INACTIVE) {
            return 'Inactive';
        }

        $onDelivery = $this->orders()
            ->whereIn('delivery_status', [Order::DELIVERY_STATUS_PICKED_UP, Order::DELIVERY_STATUS_OUT_FOR_DELIVERY])
            ->exists();

        if ($onDelivery) {
            return 'On Delivery';
        }

        if ($this->is_checked_in) {
            return 'Checked In';
        }

        if ($this->is_online) {
            return 'Online';
        }

        return 'Offline';
    }
}
