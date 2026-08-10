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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->decimal('quantity_ordered', 12, 3);
            $table->decimal('quantity_received', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
