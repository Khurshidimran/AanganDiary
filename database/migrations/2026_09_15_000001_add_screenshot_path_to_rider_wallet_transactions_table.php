<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_wallet_transactions', function (Blueprint $table) {
            $table->string('screenshot_path')->nullable()->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('rider_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('screenshot_path');
        });
    }
};
