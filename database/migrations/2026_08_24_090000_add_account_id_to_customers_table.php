<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Optional — most customers leave this blank and net through the
            // shared Accounts Receivable account (same convention vendors
            // already use for Accounts Payable). Only specific credit/
            // wholesale customers who need their own distinct ledger line
            // would get one set here.
            $table->foreignUuid('account_id')->nullable()->after('id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};
