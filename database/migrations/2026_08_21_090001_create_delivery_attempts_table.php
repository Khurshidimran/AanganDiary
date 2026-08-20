<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            $table->foreignUuid('rider_id')->nullable()->constrained('rider_profiles')->nullOnDelete();
            $table->foreignUuid('trip_id')->nullable()->constrained('rider_trips')->nullOnDelete();
            $table->string('status', 20);
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('out_for_delivery_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->decimal('cod_amount', 12, 2)->nullable();
            $table->boolean('cod_collected')->default(false);
            $table->decimal('delivery_charge', 12, 2)->nullable();
            $table->boolean('earning_credited')->default(false);
            $table->timestamps();

            $table->unique(['order_id', 'attempt_number']);
            $table->index('rider_id');
            $table->index('status');
            $table->index('trip_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');
    }
};
