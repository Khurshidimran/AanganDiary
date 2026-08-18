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
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->boolean('is_online')->default(false)->after('status');
            $table->boolean('is_checked_in')->default(false)->after('is_online');
            $table->dateTime('checked_in_at')->nullable()->after('is_checked_in');
            $table->decimal('checked_in_latitude', 10, 7)->nullable()->after('checked_in_at');
            $table->decimal('checked_in_longitude', 10, 7)->nullable()->after('checked_in_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'is_checked_in', 'checked_in_at', 'checked_in_latitude', 'checked_in_longitude']);
        });
    }
};
