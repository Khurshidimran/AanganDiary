<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('cnic', 20)->nullable();
            $table->string('vehicle_type', 20)->default('bike');
            $table->string('vehicle_number')->nullable();
            $table->string('zone')->nullable();
            $table->decimal('per_delivery_rate', 10, 2)->default(0);
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('fcm_token')->nullable();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->dateTime('last_location_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_profiles');
    }
};
