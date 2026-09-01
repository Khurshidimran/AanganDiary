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
    'product_id', 'name', 'sku', 'barcode', 'shopify_product_id', 'shopify_variant_id',
    'shopify_inventory_item_id', 'purchase_price', 'sale_price', 'compare_at_price',
    'unit_id', 'pack_size', 'is_active',
])]
class ProductVariant extends Model
{
    use HasUuid, HasLocalizedTimestamps, SoftDeletes;

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'pack_size' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function totalStock(): float
    {
        return (float) $this->stockBalances()->sum('quantity');
    }
}
