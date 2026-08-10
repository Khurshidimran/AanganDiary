<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'short_code', 'type', 'conversion_factor', 'status'])]
class Unit extends Model
{
    use HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
