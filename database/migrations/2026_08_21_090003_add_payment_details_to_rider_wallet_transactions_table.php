<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_wallet_transactions', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('amount');
            $table->string('reference_number', 60)->nullable()->after('payment_method');
            $table->date('transaction_date')->nullable()->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('rider_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'reference_number', 'transaction_date']);
        });
    }
};
