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
        if (! Schema::hasColumn('orders', 'customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->uuid('customer_id')->nullable()->after('channel_id');
            });
        }

        // MySQL foreign keys require both sides to be InnoDB (or another FK-
        // capable engine) — on at least one environment `customers` ended up
        // created as MyISAM (likely a storage-engine default mismatch at the
        // time, same class of issue already noted in the journal_entries
        // migration), which would otherwise fail the constraint below with
        // "Foreign key constraint is incorrectly formed" (errno 150).
        $customersEngine = DB::selectOne(
            'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), 'customers'],
        )?->ENGINE;

        if ($customersEngine && strtolower($customersEngine) !== 'innodb') {
            DB::statement('ALTER TABLE customers ENGINE = InnoDB');
        }

        $constraintExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'orders')
            ->where('COLUMN_NAME', 'customer_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (! $constraintExists) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
