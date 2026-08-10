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
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->string('batch_number', 100)->default('');
            $table->string('direction', 10);
            $table->string('reason', 20);
            $table->decimal('quantity', 12, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
