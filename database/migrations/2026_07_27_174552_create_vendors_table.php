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
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
