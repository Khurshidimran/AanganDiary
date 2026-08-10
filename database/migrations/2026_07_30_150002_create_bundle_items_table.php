<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('component_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->timestamps();

            $table->unique(['product_id', 'component_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_items');
    }
};
