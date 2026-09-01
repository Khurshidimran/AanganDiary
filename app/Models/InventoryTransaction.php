<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_variant_id', 'warehouse_id', 'batch_number', 'transaction_type', 'quantity',
    'quantity_before', 'quantity_after', 'reference_type', 'reference_id', 'user_id',
    'notes', 'transaction_date',
])]
class InventoryTransaction extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    public const TYPE_PURCHASE_RECEIPT = 'purchase_receipt';
    public const TYPE_SALE = 'sale';
    public const TYPE_ORDER_ALLOCATION = 'order_allocation';
    public const TYPE_ORDER_RELEASE = 'order_release';
    public const TYPE_PRODUCTION = 'production';
    public const TYPE_STOCK_TRANSFER_IN = 'stock_transfer_in';
    public const TYPE_STOCK_TRANSFER_OUT = 'stock_transfer_out';
    public const TYPE_CUSTOMER_RETURN = 'customer_return';
    public const TYPE_VENDOR_RETURN = 'vendor_return';
    public const TYPE_WASTAGE = 'wastage';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_STOCK_ADJUSTMENT = 'stock_adjustment';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'transaction_date' => 'datetime',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
