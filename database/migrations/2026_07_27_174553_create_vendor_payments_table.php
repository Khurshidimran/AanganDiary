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
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('method', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
};
