<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chart of accounts. Deliberately has no stored balance column — unlike
 * RiderProfile::wallet_balance, GL balances need to be correct as of an
 * arbitrary historical date (Trial Balance, Ledger, P&L all need this), so
 * balanceAsOf()/balanceBetween() always recompute from journal_entry_lines
 * rather than trusting a denormalized running total.
 */
#[Fillable(['code', 'name', 'type', 'parent_id', 'is_system', 'status'])]
class Account extends Model
{
    use HasUuid, SoftDeletes;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function normalBalance(): string
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true) ? 'debit' : 'credit';
    }

    /**
     * Signed so a debit-normal account (asset/expense) reads positive when
     * debits exceed credits, and a credit-normal account (liability/equity/
     * revenue) reads positive when credits exceed debits.
     */
    public function balanceAsOf(?Carbon $date = null): float
    {
        $query = $this->lines()->whereHas(
            'journalEntry',
            fn ($q) => $q->where('status', JournalEntry::STATUS_POSTED)
                ->when($date, fn ($qq) => $qq->where('entry_date', '<=', $date)),
        );

        $debit = (float) $query->sum('debit');
        $credit = (float) $query->sum('credit');

        return $this->normalBalance() === 'debit' ? $debit - $credit : $credit - $debit;
    }

    public function balanceBetween(Carbon $from, Carbon $to): float
    {
        $query = $this->lines()->whereHas(
            'journalEntry',
            fn ($q) => $q->where('status', JournalEntry::STATUS_POSTED)
                ->whereBetween('entry_date', [$from, $to]),
        );

        $debit = (float) $query->sum('debit');
        $credit = (float) $query->sum('credit');

        return $this->normalBalance() === 'debit' ? $debit - $credit : $credit - $debit;
    }
}
