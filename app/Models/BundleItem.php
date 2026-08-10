<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'component_variant_id', 'quantity'])]
class BundleItem extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'component_variant_id');
    }
}
