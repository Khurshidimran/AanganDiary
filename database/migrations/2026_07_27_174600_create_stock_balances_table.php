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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->string('batch_number', 100)->default('');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'warehouse_id', 'batch_number'], 'stock_balances_variant_warehouse_batch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
