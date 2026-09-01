<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'status', 'chart_account_id'])]
class ExpenseCategory extends Model
{
    use HasUuid, HasLocalizedTimestamps, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'chart_account_id');
    }
}
