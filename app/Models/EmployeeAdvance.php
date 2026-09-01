<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'amount', 'date_given', 'reason', 'remaining_balance', 'status', 'recorded_by'])]
class EmployeeAdvance extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SETTLED = 'settled';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'date_given' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'employee_advance_id');
    }
}
