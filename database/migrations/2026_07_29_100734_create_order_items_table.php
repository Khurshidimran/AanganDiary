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
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shopify_product_id')->nullable();
            $table->string('shopify_variant_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('product_name');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();

            $table->index('shopify_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
