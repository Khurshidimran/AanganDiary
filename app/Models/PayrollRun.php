<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['period_start', 'period_end', 'status', 'generated_by', 'approved_by', 'approved_at', 'paid_at', 'notes'])]
class PayrollRun extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeMarkedPaid(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Items can only be edited (adjustments added/removed) while still a
     * draft — once approved, the payslip is locked, matching how the rest
     * of this app treats an approval step as a hard boundary.
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
