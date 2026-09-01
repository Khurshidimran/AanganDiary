<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'url', 'sort_order'])]
class ProductImage extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
