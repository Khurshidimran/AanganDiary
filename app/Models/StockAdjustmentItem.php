<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stock_adjustment_id', 'product_variant_id', 'batch_number', 'direction', 'reason', 'quantity', 'notes'])]
class StockAdjustmentItem extends Model
{
    use HasUuid;

    public const DIRECTION_INCREASE = 'increase';
    public const DIRECTION_DECREASE = 'decrease';

    public const REASON_CORRECTION = 'stock_adjustment';
    public const REASON_WASTAGE = 'wastage';
    public const REASON_DAMAGE = 'damage';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
