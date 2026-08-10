<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'status'])]
class Brand extends Model
{
    use HasUuid, SoftDeletes;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
