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
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('short_code', 10);
            $table->string('type', 20);
            $table->decimal('conversion_factor', 12, 4);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('name');
            $table->unique('short_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
