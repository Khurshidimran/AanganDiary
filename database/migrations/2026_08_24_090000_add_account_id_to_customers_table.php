<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded: on at least one environment this column was already added
        // by hand before this migration was written, so re-adding it
        // unconditionally would fail with "column already exists."
        if (! Schema::hasColumn('customers', 'account_id')) {
            Schema::table('customers', function (Blueprint $table) {
                // Optional — most customers leave this blank and net through
                // the shared Accounts Receivable account (same convention
                // vendors already use for Accounts Payable). Only specific
                // credit/wholesale customers who need their own distinct
                // ledger line would get one set here.
                $table->uuid('account_id')->nullable()->after('id');
            });
        }

        $constraintExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'customers')
            ->where('COLUMN_NAME', 'account_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (! $constraintExists) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
