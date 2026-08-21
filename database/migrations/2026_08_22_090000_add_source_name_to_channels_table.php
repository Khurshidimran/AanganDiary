<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // Shopify's own order-origin identifier (e.g. 'web' for Online
            // Store, 'shopify_draft_order' for Draft Orders) — lets sync
            // auto-route an incoming order to the right channel. Null for
            // channels that aren't a direct Shopify-source match (WhatsApp,
            // Phone, and the generic "Shopify" fallback itself).
            $table->string('source_name', 100)->nullable()->unique()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('source_name');
        });
    }
};
