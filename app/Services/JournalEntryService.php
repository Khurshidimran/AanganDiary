<?php

namespace App\Services;

use App\Exceptions\InvalidJournalEntryStateException;
use App\Exceptions\UnbalancedJournalEntryException;
use App\Models\Account;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Every journal entry (manual voucher or system-posted) is created already
 * posted — there is no draft stage, mirroring how RiderWalletController's
 * settle-cod/pay-earnings/adjust actions post immediately. Corrections
 * happen via void(), which never mutates or deletes a posted entry: it
 * creates a mirror-image reversing entry and flags the original voided,
 * matching the immutable-ledger convention used by RiderWalletTransaction/
 * InventoryTransaction/PayrollRunItem/AuditLog.
 */
class JournalEntryService
{
    /**
     * @param  list<array{account_id: string, debit?: float, credit?: float, description?: ?string}>  $lines
     */
    public function post(
        array $lines,
        string $type,
        string|Carbon $entryDate,
        ?string $narration = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        string $source = JournalEntry::SOURCE_MANUAL,
    ): JournalEntry {
        $totalDebit = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 2);
        $totalCredit = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 2);

        if (count($lines) < 2) {
            throw new UnbalancedJournalEntryException('A journal entry needs at least two lines.');
        }

        if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
            throw new UnbalancedJournalEntryException(
                "Journal entry does not balance: total debit {$totalDebit} vs total credit {$totalCredit}.",
            );
        }

        return DB::transaction(function () use ($lines, $type, $entryDate, $narration, $referenceType, $referenceId, $source) {
            $entry = JournalEntry::create([
                'entry_number' => $this->nextEntryNumber(),
                'entry_date' => $entryDate,
                'type' => $type,
                'source' => $source,
                'status' => JournalEntry::STATUS_POSTED,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'narration' => $narration,
                'created_by' => Auth::id(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public function postSimple(
        string $type,
        string|Carbon $entryDate,
        Account $debitAccount,
        Account $creditAccount,
        float $amount,
        ?string $narration = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        string $source = JournalEntry::SOURCE_MANUAL,
    ): JournalEntry {
        return $this->post(
            lines: [
                ['account_id' => $debitAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $creditAccount->id, 'debit' => 0, 'credit' => $amount],
            ],
            type: $type,
            entryDate: $entryDate,
            narration: $narration,
            referenceType: $referenceType,
            referenceId: $referenceId,
            source: $source,
        );
    }

    public function hasPostedEntryFor(string $referenceType, string $referenceId): bool
    {
        return JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->exists();
    }

    /**
     * Voids whatever posted entry exists for this reference, if any — used
     * by integration hooks (e.g. order cancellation) that don't hold the
     * JournalEntry instance directly.
     */
    public function voidEntryFor(string $referenceType, string $referenceId, string $reason): ?JournalEntry
    {
        $entry = JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        return $entry ? $this->void($entry, $reason) : null;
    }

    public function void(JournalEntry $entry, string $reason): JournalEntry
    {
        if ($entry->isVoided()) {
            throw new InvalidJournalEntryStateException("Entry {$entry->entry_number} is already voided.");
        }

        return DB::transaction(function () use ($entry, $reason) {
            $entry->loadMissing('lines');

            $reversal = JournalEntry::create([
                'entry_number' => $this->nextEntryNumber(),
                'entry_date' => now()->toDateString(),
                'type' => $entry->type,
                'source' => JournalEntry::SOURCE_REVERSAL,
                'status' => JournalEntry::STATUS_POSTED,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'narration' => "Reversal of {$entry->entry_number}: {$reason}",
                'created_by' => Auth::id(),
                'voided_journal_entry_id' => $entry->id,
            ]);

            foreach ($entry->lines as $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => $line->description,
                ]);
            }

            $entry->update(['status' => JournalEntry::STATUS_VOIDED]);

            return $reversal->load('lines.account');
        });
    }

    private function nextEntryNumber(): string
    {
        $next = JournalEntry::count() + 1;

        return 'JE-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
