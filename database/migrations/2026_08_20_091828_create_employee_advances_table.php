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
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date_given');
            $table->string('reason')->nullable();
            // Deductions are manual (one-off, entered by staff on a specific
            // payroll run) rather than auto-amortized — this just tracks how
            // much is left so staff have visibility, not an automated schedule.
            $table->decimal('remaining_balance', 12, 2);
            $table->string('status', 20)->default('active');
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
