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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transfer_number')->unique();
            $table->foreignUuid('from_warehouse_id')->constrained('warehouses');
            $table->foreignUuid('to_warehouse_id')->constrained('warehouses');
            $table->string('status', 20)->default('draft');
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
