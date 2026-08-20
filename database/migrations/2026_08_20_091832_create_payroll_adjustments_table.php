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
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_run_item_id')->constrained('payroll_run_items')->cascadeOnDelete();
            // Set only when this deduction is being applied against a
            // specific advance — reduces that advance's remaining_balance.
            $table->foreignUuid('employee_advance_id')->nullable()->constrained('employee_advances')->nullOnDelete();
            $table->string('type', 20); // addition | deduction
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
