<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Set by staff at assignment time (DispatchController::assign) —
            // when the order should actually go out, not when it was
            // assigned. Both nullable/optional.
            $table->dateTime('scheduled_dispatch_at')->nullable()->after('assigned_at');
            $table->text('rider_instructions')->nullable()->after('scheduled_dispatch_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['scheduled_dispatch_at', 'rider_instructions']);
        });
    }
};
