<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payroll_run_item_id', 'employee_advance_id', 'type', 'label', 'amount', 'recorded_by'])]
class PayrollAdjustment extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    public const TYPE_ADDITION = 'addition';
    public const TYPE_DEDUCTION = 'deduction';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payrollRunItem(): BelongsTo
    {
        return $this->belongsTo(PayrollRunItem::class);
    }

    public function employeeAdvance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAdvance::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
