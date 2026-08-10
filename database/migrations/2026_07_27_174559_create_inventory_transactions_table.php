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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->string('batch_number', 100)->default('');
            $table->string('transaction_type', 30);
            $table->decimal('quantity', 12, 3);
            $table->decimal('quantity_before', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->string('reference_type', 100)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('transaction_date');
            $table->timestamps();

            $table->index(['product_variant_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
