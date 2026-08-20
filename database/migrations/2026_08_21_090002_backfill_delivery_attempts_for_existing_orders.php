<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time data backfill: orders assigned to a rider before delivery-attempt
 * tracking existed get a single attempt_number=1 row mirroring their current
 * state, so the new Rider Account page doesn't show blank history for them.
 *
 * delivery_charge/earning_credited are deliberately left null/false rather
 * than reconstructed from the rider's CURRENT per_delivery_rate — that rate
 * may have changed since the order was actually delivered, so a guessed
 * value would misrepresent history. This is an honest, documented gap: the
 * Earnings tab's per-row Payment Status for these legacy rows will show as
 * contributing nothing, but the ledger-derived summary totals (which read
 * rider_wallet_transactions, not this table) stay fully correct regardless.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->whereNotNull('rider_id')
            ->orderBy('created_at')
            ->chunkById(200, function ($orders) {
                $rows = [];

                foreach ($orders as $order) {
                    $completedAt = $order->delivered_at
                        ?? (in_array($order->delivery_status, ['failed', 'returned'], true) ? $order->updated_at : null);

                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'order_id' => $order->id,
                        'attempt_number' => 1,
                        'rider_id' => $order->rider_id,
                        'trip_id' => null,
                        'status' => $order->delivery_status,
                        'assigned_at' => $order->assigned_at,
                        'picked_up_at' => $order->picked_up_at,
                        'out_for_delivery_at' => null,
                        'delivered_at' => $order->delivered_at,
                        'completed_at' => $completedAt,
                        'failure_reason' => $order->delivery_failure_reason,
                        'cod_amount' => $order->cod_amount,
                        'cod_collected' => $order->cod_collected,
                        'delivery_charge' => null,
                        'earning_credited' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows) {
                    DB::table('delivery_attempts')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // No data restore needed — dropping delivery_attempts (previous
        // migration's down()) already clears everything this inserted.
    }
};
