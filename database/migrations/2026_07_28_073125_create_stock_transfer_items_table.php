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
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->string('batch_number', 100)->default('');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
