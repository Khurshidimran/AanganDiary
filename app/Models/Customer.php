<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['account_id', 'name', 'phone', 'email', 'notes'])]
class Customer extends Model
{
    use HasUuid, HasLocalizedTimestamps, SoftDeletes;

    /**
     * Optional — most customers leave this unset and net through the shared
     * Accounts Receivable account (mirrors how vendors default to the shared
     * Accounts Payable account). Only set for specific credit/wholesale
     * customers who need their own distinct ledger line.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

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
