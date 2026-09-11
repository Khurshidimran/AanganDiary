<?php

namespace Database\Seeders;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Seeder;

/**
 * One-off correction: permanently removes every journal entry (and its
 * lines, via the existing cascade FK) dated before 2026-09-01 — undoing
 * BackfillSalesAccountingSeeder having gone further back than intended.
 *
 * This business deliberately starts its formal accounting from 2026-09-01
 * (see the Opening Balances feature) — anything dated earlier was never
 * meant to be part of that ledger. Hard-deleted rather than voided:
 * voiding creates an equal-and-opposite reversing entry dated the day it's
 * run, which for ~387 entries would mean ~387 same-day reversal rows
 * cluttering the ledger — appropriate for reversing a real business event,
 * not for correcting an over-broad script that should simply not have
 * created these in the first place.
 *
 * Deliberately NOT wired into DatabaseSeeder::run() — run once, by hand:
 *   php artisan db:seed --class=RemovePreSeptemberAccountingSeeder
 * Safe to re-run: a second run finds nothing left before the cutover and
 * does nothing.
 */
class RemovePreSeptemberAccountingSeeder extends Seeder
{
    private const CUTOFF_DATE = '2026-09-01';

    public function run(): void
    {
        $entryIds = JournalEntry::where('entry_date', '<', self::CUTOFF_DATE)->pluck('id');
        $entryCount = $entryIds->count();

        // Deleted explicitly rather than relying on the journal_entry_lines
        // FK's cascadeOnDelete — verified against real data that a bulk
        // delete on the parent does not reliably cascade its lines in this
        // environment, which would otherwise leave orphaned line rows behind.
        $lineCount = JournalEntryLine::whereIn('journal_entry_id', $entryIds)->delete();

        JournalEntry::whereIn('id', $entryIds)->delete();

        $this->command?->info("Deleted {$entryCount} journal entry(ies) and {$lineCount} line(s) dated before ".self::CUTOFF_DATE.'.');
    }
}
