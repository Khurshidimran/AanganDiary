<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Raw value Shopify sent for this order (e.g. 'web',
            // 'shopify_draft_order', or a third-party app's identifier) —
            // kept even when it doesn't match any seeded Channel, so the
            // real value is always visible/auditable rather than silently
            // dropped, and so channel mapping can be refined later without
            // needing to re-fetch the order from Shopify.
            $table->string('shopify_source_name', 100)->nullable()->after('channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shopify_source_name');
        });
    }
};
