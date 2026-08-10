<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id', 'brand_id', 'name', 'slug', 'description', 'unit_id',
    'tax_rate', 'image', 'tags', 'is_bundle', 'supply_type', 'track_inventory',
    'track_batch', 'track_expiry', 'status',
])]
class Product extends Model
{
    use HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'is_bundle' => 'boolean',
            'track_inventory' => 'boolean',
            'track_batch' => 'boolean',
            'track_expiry' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }
}
