<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'warehouse_id', 'phone', 'cnic', 'vehicle_type', 'vehicle_number',
    'zone', 'per_delivery_rate', 'wallet_balance', 'status', 'fcm_token',
    'last_latitude', 'last_longitude', 'last_location_at',
])]
class RiderProfile extends Model
{
    use HasUuid, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ON_LEAVE = 'on_leave';

    protected function casts(): array
    {
        return [
            'per_delivery_rate' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
            'last_latitude' => 'decimal:7',
            'last_longitude' => 'decimal:7',
            'last_location_at' => 'datetime',
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
}
