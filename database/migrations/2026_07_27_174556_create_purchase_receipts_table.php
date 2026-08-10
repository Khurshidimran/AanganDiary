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
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number')->unique();
            $table->foreignUuid('purchase_order_id')->constrained();
            $table->foreignUuid('vendor_id')->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->date('receipt_date');
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
