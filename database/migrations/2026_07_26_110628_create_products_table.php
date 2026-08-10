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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->string('image')->nullable();
            $table->text('tags')->nullable();
            $table->boolean('is_bundle')->default(false);
            $table->string('supply_type', 20)->default('purchased');
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
