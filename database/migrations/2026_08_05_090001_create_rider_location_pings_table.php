<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_location_pings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rider_id')->constrained('rider_profiles')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['rider_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_location_pings');
    }
};
