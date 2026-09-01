<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\HasLocalizedTimestamps;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'entry_number', 'entry_date', 'type', 'source', 'status',
    'reference_type', 'reference_id', 'narration', 'created_by', 'voided_journal_entry_id',
])]
class JournalEntry extends Model
{
    use HasUuid, HasLocalizedTimestamps;

    public const TYPE_JOURNAL = 'journal';
    public const TYPE_CASH_PAYMENT = 'cash_payment';
    public const TYPE_CASH_RECEIPT = 'cash_receipt';
    public const TYPE_BANK_PAYMENT = 'bank_payment';
    public const TYPE_BANK_RECEIPT = 'bank_receipt';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_REVERSAL = 'reversal';

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The reversing entry created when this one is voided (null until then).
     */
    public function reversal(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'voided_journal_entry_id');
    }

    public function voidedEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'voided_journal_entry_id');
    }

    public function totalDebit(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }
}
