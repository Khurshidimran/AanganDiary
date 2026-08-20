<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One check-in-to-check-out work session for a rider. Purely a reporting/
 * history concept — RiderProfile.is_checked_in/checked_in_at (flipped in
 * place by RiderController/RiderStatusController) remain the live source of
 * truth for "is this rider checked in right now"; this table exists so that
 * past sessions aren't lost the moment the rider checks out again.
 */
#[Fillable([
    'rider_id', 'warehouse_id', 'checked_in_at', 'checked_out_at',
    'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude', 'status',
])]
class RiderTrip extends Model
{
    use HasUuid;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class, 'rider_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class, 'trip_id');
    }
}
