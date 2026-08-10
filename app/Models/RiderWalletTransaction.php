<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A signed ledger of a rider's net position with the company: positive
 * balance means the rider is holding company money (owes it back, e.g.
 * uncollected COD settlements); negative means the company owes the rider
 * (e.g. credited delivery earnings not yet paid out). Mirrors the append-only,
 * before/after-balance pattern used by InventoryTransaction/InventoryService.
 */
#[Fillable([
    'rider_id', 'transaction_type', 'amount', 'balance_before', 'balance_after',
    'reference_type', 'reference_id', 'recorded_by', 'notes',
])]
class RiderWalletTransaction extends Model
{
    use HasUuid;

    public const TYPE_COD_COLLECTED = 'cod_collected';
    public const TYPE_COD_SETTLED = 'cod_settled';
    public const TYPE_EARNING_CREDITED = 'earning_credited';
    public const TYPE_EARNING_PAID = 'earning_paid';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(RiderProfile::class, 'rider_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
