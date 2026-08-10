<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rider_id')->constrained('rider_profiles')->cascadeOnDelete();
            $table->string('transaction_type', 30);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type', 30)->nullable();
            $table->string('reference_id', 36)->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_wallet_transactions');
    }
};
