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
        Schema::create('shopify_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sync_type', 30);
            $table->string('status', 20)->default('running');
            $table->unsignedInteger('items_processed')->default(0);
            $table->unsignedInteger('items_created')->default(0);
            $table->unsignedInteger('items_updated')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->text('error_summary')->nullable();
            $table->foreignUuid('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index('sync_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_sync_logs');
    }
};
