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
        Schema::table('orders', function (Blueprint $table) {
            // Rider-chosen delivery-route position, set via the checkout
            // endpoint — purely a display/planning order, independent of
            // delivery_status (see App\Http\Controllers\Api\Rider\DeliveryController::checkout()).
            $table->unsignedInteger('route_sequence')->nullable()->after('rider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('route_sequence');
        });
    }
};
