<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Defaults every existing order to 'cash' — this is exactly what
            // fixes Receivables Aging showing orders that were never really
            // meant to be paid later: legacy orders correctly stop appearing
            // once the report filters on this field, since none of them were
            // ever actually credit sales.
            $table->string('payment_type', 20)->default('cash')->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
