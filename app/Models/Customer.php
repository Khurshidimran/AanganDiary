<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone', 'email', 'notes'])]
class Customer extends Model
{
    use HasUuid, SoftDeletes;

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Assumes `addresses` is already eager-loaded (same discipline as
     * Order::itemsSummary()) — avoids an N+1 when called across a list.
     */
    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses->firstWhere('is_default', true) ?? $this->addresses->first();
    }
}
