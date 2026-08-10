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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('shopify_order_id')->unique();
            $table->string('shopify_order_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('order_status', 20)->default('pending');
            $table->string('payment_status', 20)->default('pending');
            $table->string('delivery_status', 20)->default('pending');
            $table->string('currency', 10)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_outstanding', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('shopify_created_at')->nullable();
            $table->foreignUuid('rider_id')->nullable()->constrained('rider_profiles')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->decimal('cod_amount', 12, 2)->nullable();
            $table->boolean('cod_collected')->default(false);
            $table->string('delivery_failure_reason')->nullable();
            $table->string('pod_photo_path')->nullable();
            $table->string('pod_signature_path')->nullable();
            $table->dateTime('pod_captured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_status');
            $table->index('payment_status');
            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
