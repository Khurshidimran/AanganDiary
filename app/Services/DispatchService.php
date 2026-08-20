<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Drives an order through the delivery lifecycle once it's confirmed. Every
 * transition here can be triggered either by staff on the rider's behalf
 * (admin dispatch board) or by the rider themselves via the mobile API.
 */
class DispatchService
{
    public function __construct(
        private readonly RiderWalletService $wallet,
        private readonly OrderFulfillmentService $fulfillment,
        private readonly FcmService $fcm,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function assign(
        Order $order,
        RiderProfile $rider,
        ?string $riderInstructions = null,
        ?\DateTimeInterface $scheduledDispatchAt = null,
    ): void {
        $updates = [
            'rider_id' => $rider->id,
            'assigned_at' => now(),
            'delivery_status' => Order::DELIVERY_STATUS_ASSIGNED,
            // total_outstanding is Shopify's own precise remaining balance —
            // correctly handles partially-paid orders (e.g. an online deposit),
            // not just the fully-paid/fully-unpaid binary the total alone gives.
            'cod_amount' => $order->total_outstanding ?? $order->total,
        ];

        // Only touched when actually provided — a reassignment where staff
        // leaves these blank keeps whatever was set previously rather than
        // silently wiping it out.
        if ($riderInstructions !== null) {
            $updates['rider_instructions'] = $riderInstructions;
        }

        if ($scheduledDispatchAt !== null) {
            $updates['scheduled_dispatch_at'] = $scheduledDispatchAt;
        }

        $order->update($updates);

        if ($rider->fcm_token) {
            $this->fcm->sendToToken(
                token: $rider->fcm_token,
                title: 'New delivery assigned',
                body: "Order {$order->shopify_order_number} has been assigned to you.",
                data: ['order_id' => $order->id, 'type' => 'delivery_assigned'],
            );
        }
    }

    /**
     * Reverts an assigned-but-not-yet-picked-up order back to unassigned,
     * clearing everything the assignment set — a clean slate for whoever
     * picks it up next, not just a rider_id swap.
     */
    public function unassign(Order $order): void
    {
        $order->update([
            'rider_id' => null,
            'delivery_status' => Order::DELIVERY_STATUS_PENDING,
            'assigned_at' => null,
            'scheduled_dispatch_at' => null,
            'rider_instructions' => null,
            'cod_amount' => 0,
        ]);
    }

    public function markPickedUp(Order $order): void
    {
        $order->update([
            'delivery_status' => Order::DELIVERY_STATUS_PICKED_UP,
            'picked_up_at' => now(),
        ]);
    }

    public function markOutForDelivery(Order $order): void
    {
        $order->update(['delivery_status' => Order::DELIVERY_STATUS_OUT_FOR_DELIVERY]);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{succeeded: list<Order>, skipped: list<array{order: Order, reason: string}>}
     */
    public function markManyAssigned(Collection $orders, RiderProfile $rider): array
    {
        return $this->bulkTransition(
            $orders,
            fn (Order $o) => $o->canBeAssigned() && ! $o->isCancelled(),
            fn (Order $o) => $this->assign($o, $rider),
        );
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{succeeded: list<Order>, skipped: list<array{order: Order, reason: string}>}
     */
    public function markManyPickedUp(Collection $orders): array
    {
        return $this->bulkTransition(
            $orders,
            fn (Order $o) => $o->canBeMarkedPickedUp(),
            fn (Order $o) => $this->markPickedUp($o),
        );
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{succeeded: list<Order>, skipped: list<array{order: Order, reason: string}>}
     */
    public function markManyOutForDelivery(Collection $orders): array
    {
        return $this->bulkTransition(
            $orders,
            fn (Order $o) => $o->canBeMarkedOutForDelivery(),
            fn (Order $o) => $this->markOutForDelivery($o),
        );
    }

    /**
     * Applies a transition to each order that's eligible, skipping (not
     * failing) the rest — mirrors the per-row `@if ($order->canBeMarkedX())`
     * guard that already hides the action button for ineligible orders in
     * the single-order UI, just with explicit accounting for a bulk caller.
     *
     * @param  Collection<int, Order>  $orders
     * @return array{succeeded: list<Order>, skipped: list<array{order: Order, reason: string}>}
     */
    private function bulkTransition(Collection $orders, Closure $isEligible, Closure $apply): array
    {
        $succeeded = [];
        $skipped = [];

        foreach ($orders as $order) {
            if (! $isEligible($order)) {
                $reason = $order->isCancelled() ? 'cancelled in Shopify' : "currently {$order->delivery_status}";
                $skipped[] = ['order' => $order, 'reason' => $reason];

                continue;
            }

            $apply($order);
            $succeeded[] = $order;
        }

        return ['succeeded' => $succeeded, 'skipped' => $skipped];
    }

    public function markDelivered(Order $order, ?string $podPhotoPath = null, ?string $podSignaturePath = null): void
    {
        DB::transaction(function () use ($order, $podPhotoPath, $podSignaturePath) {
            $order->update([
                'delivery_status' => Order::DELIVERY_STATUS_DELIVERED,
                'delivered_at' => now(),
                'pod_photo_path' => $podPhotoPath,
                'pod_signature_path' => $podSignaturePath,
                'pod_captured_at' => ($podPhotoPath || $podSignaturePath) ? now() : null,
            ]);

            $rider = $order->rider;

            if ((float) $order->cod_amount > 0) {
                $this->wallet->postTransaction(
                    rider: $rider,
                    transactionType: RiderWalletTransaction::TYPE_COD_COLLECTED,
                    amount: (float) $order->cod_amount,
                    referenceType: 'orders',
                    referenceId: $order->id,
                    notes: "COD collected for order #{$order->shopify_order_number}",
                );
                $order->update(['cod_collected' => true]);
            }

            if ((float) $rider->per_delivery_rate > 0) {
                $this->wallet->postTransaction(
                    rider: $rider,
                    transactionType: RiderWalletTransaction::TYPE_EARNING_CREDITED,
                    amount: -(float) $rider->per_delivery_rate,
                    referenceType: 'orders',
                    referenceId: $order->id,
                    notes: "Delivery fee for order #{$order->shopify_order_number}",
                );
            }

            $this->accounting->postCogsEntry($order);
        });
    }

    public function markFailed(Order $order, ?string $reason): void
    {
        $order->update([
            'delivery_status' => Order::DELIVERY_STATUS_FAILED,
            'delivery_failure_reason' => $reason,
        ]);
    }

    /**
     * Returned means the goods are confirmed back at the warehouse — unlike a
     * failed attempt (which may be retried), this releases the allocated stock.
     */
    public function markReturned(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->fulfillment->releaseStock($order);
            $order->update(['delivery_status' => Order::DELIVERY_STATUS_RETURNED]);
        });
    }
}
