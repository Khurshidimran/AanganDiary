<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * On at least one environment, a batch of tables ended up created as MyISAM
 * instead of InnoDB (likely a storage-engine default mismatch at the moment
 * they were created — the same class of issue already flagged in the
 * journal_entries migration's own comment). MySQL foreign keys require both
 * sides of a constraint to be InnoDB, so every later migration that adds an
 * FK referencing one of these tables (customers, accounts, etc.) fails with
 * "Foreign key constraint is incorrectly formed" (errno 150) until they're
 * converted. Data-driven rather than a hardcoded table list, so this is a
 * no-op wherever everything is already InnoDB (e.g. a fresh install).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = DB::select(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND ENGINE != 'InnoDB' AND TABLE_TYPE = 'BASE TABLE'",
            [DB::getDatabaseName()],
        );

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE `{$table->TABLE_NAME}` ENGINE = InnoDB");
        }
    }

    public function down(): void
    {
        // Converting back to MyISAM would only reintroduce the bug this
        // fixes — nothing to reverse.
    }
};
