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
        Schema::create('shopify_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shopify_webhook_id')->unique();
            $table->string('topic', 100);
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('received');
            $table->text('error_message')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->index('topic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_webhook_events');
    }
};
