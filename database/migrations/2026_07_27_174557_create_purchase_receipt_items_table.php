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
        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_order_item_id')->constrained();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->string('batch_number', 100)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_items');
    }
};
