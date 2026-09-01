<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Models\DeliveryAttempt;
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
        private readonly RiderTripService $trips,
    ) {
    }

    /**
     * The delivery attempt currently in progress (or most recently
     * completed) for this order — every DeliveryAttempt row is immutable
     * once its outcome is set, so "current" just means the highest attempt
     * number on record.
     */
    private function currentAttempt(Order $order): ?DeliveryAttempt
    {
        return DeliveryAttempt::where('order_id', $order->id)->latest('attempt_number')->first();
    }

    /**
     * Always resolves the rider fresh from rider_id rather than the
     * `rider` relation — an Order instance reused across a full
     * assign->return->reassign cycle (a seeder, an Artisan command, any
     * code that doesn't re-fetch between steps) would otherwise keep
     * Eloquent's cached relation pointed at whichever rider first lazy
     * loaded it, silently crediting wallet postings to the wrong rider
     * after a reassignment. rider_id itself is never stale — update()
     * always writes it — only the cached relation object can be.
     */
    private function currentRider(Order $order): ?RiderProfile
    {
        return $order->rider_id ? RiderProfile::find($order->rider_id) : null;
    }

    /**
     * @return ?string A stock/warehouse warning message if allocation
     *                  couldn't fully complete, or null if it either
     *                  succeeded or wasn't attempted. Never blocks the
     *                  assignment itself — mirrors OrderController::confirm(),
     *                  which treats stock the same way: attempted when
     *                  possible, but never a reason to stop an operational
     *                  action, since this client doesn't manage inventory
     *                  closely enough for a shortage to be trustworthy
     *                  grounds to block a real rider/delivery decision.
     */
    public function assign(
        Order $order,
        RiderProfile $rider,
        ?string $riderInstructions = null,
        ?\DateTimeInterface $scheduledDispatchAt = null,
    ): ?string {
        $stockWarning = null;

        // A returned order already had its stock released back to the
        // warehouse (see markReturned below) — putting it back out for
        // another delivery attempt needs a fresh allocation, the same as
        // the original confirm() did. Attempted best-effort (still deducts
        // whatever batches ARE available), but a shortage no longer blocks
        // the reassignment — see the docblock above.
        if ($order->delivery_status === Order::DELIVERY_STATUS_RETURNED) {
            try {
                $this->fulfillment->allocateStock($order);
            } catch (InsufficientStockException|WarehouseNotConfiguredException $e) {
                $stockWarning = $e->getMessage();
            }
        }

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

        DeliveryAttempt::create([
            'order_id' => $order->id,
            'attempt_number' => (DeliveryAttempt::where('order_id', $order->id)->max('attempt_number') ?? 0) + 1,
            'rider_id' => $rider->id,
            'status' => Order::DELIVERY_STATUS_ASSIGNED,
            'assigned_at' => $updates['assigned_at'],
            'cod_amount' => $updates['cod_amount'],
        ]);

        if ($rider->fcm_token) {
            $this->fcm->sendToToken(
                token: $rider->fcm_token,
                title: 'New delivery assigned',
                body: "Order {$order->shopify_order_number} has been assigned to you.",
                data: ['order_id' => $order->id, 'type' => 'delivery_assigned'],
            );
        }

        return $stockWarning;
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

        // The attempt this assignment created never became a real physical
        // delivery attempt — remove it so attempt numbers only ever count
        // attempts that actually left the warehouse with a rider.
        DeliveryAttempt::where('order_id', $order->id)
            ->where('status', Order::DELIVERY_STATUS_ASSIGNED)
            ->latest('attempt_number')
            ->first()
            ?->delete();
    }

    public function updateInstructions(Order $order, ?string $instructions): void
    {
        $order->update(['rider_instructions' => $instructions]);
    }

    public function markPickedUp(Order $order, ?string $popPhotoPath = null): void
    {
        $order->update([
            'delivery_status' => Order::DELIVERY_STATUS_PICKED_UP,
            'picked_up_at' => now(),
            'pop_photo_path' => $popPhotoPath,
            'pop_captured_at' => $popPhotoPath ? now() : null,
        ]);

        $this->currentAttempt($order)?->update([
            'status' => Order::DELIVERY_STATUS_PICKED_UP,
            'picked_up_at' => now(),
            'trip_id' => $this->trips->currentOpenTrip($this->currentRider($order))?->id,
        ]);
    }

    public function markOutForDelivery(Order $order): void
    {
        $order->update(['delivery_status' => Order::DELIVERY_STATUS_OUT_FOR_DELIVERY]);

        $this->currentAttempt($order)?->update([
            'status' => Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
            'out_for_delivery_at' => now(),
        ]);
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

            try {
                $apply($order);
            } catch (InsufficientStockException|WarehouseNotConfiguredException $e) {
                $skipped[] = ['order' => $order, 'reason' => $e->getMessage()];

                continue;
            }

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

            $rider = $this->currentRider($order);

            // An Online Transfer order is paid straight into the business's
            // own bank account at the delivery spot — the rider never
            // physically holds that money, so crediting their wallet as if
            // they collected cash would misrepresent what they're actually
            // holding. Still marked collected for tracking; just no wallet
            // transaction backing it.
            if ((float) $order->cod_amount > 0) {
                if ($order->payment_type !== Order::PAYMENT_TYPE_ONLINE) {
                    $this->wallet->postTransaction(
                        rider: $rider,
                        transactionType: RiderWalletTransaction::TYPE_COD_COLLECTED,
                        amount: (float) $order->cod_amount,
                        referenceType: 'orders',
                        referenceId: $order->id,
                        notes: "COD collected for order #{$order->shopify_order_number}",
                    );
                }

                $order->update(['cod_collected' => true]);
            }

            $earningCredited = (float) $rider->per_delivery_rate > 0;

            if ($earningCredited) {
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

            $this->currentAttempt($order)?->update([
                'status' => Order::DELIVERY_STATUS_DELIVERED,
                'delivered_at' => $order->delivered_at,
                'completed_at' => $order->delivered_at,
                'cod_amount' => $order->cod_amount,
                'cod_collected' => $order->cod_collected,
                'delivery_charge' => $earningCredited ? $rider->per_delivery_rate : null,
                'earning_credited' => $earningCredited,
            ]);
        });
    }

    public function markFailed(Order $order, ?string $reason, ?\DateTimeInterface $scheduledDispatchAt = null): void
    {
        $updates = [
            'delivery_status' => Order::DELIVERY_STATUS_FAILED,
            'delivery_failure_reason' => $reason,
        ];

        // Captured as early as the promise is made to the customer, rather
        // than waiting until the eventual reassignment — see markReturned()
        // for the fuller reasoning (the same field, same intent).
        if ($scheduledDispatchAt !== null) {
            $updates['scheduled_dispatch_at'] = $scheduledDispatchAt;
        }

        $order->update($updates);

        $this->currentAttempt($order)?->update([
            'status' => Order::DELIVERY_STATUS_FAILED,
            'completed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Returned means the goods are confirmed back at the warehouse — unlike a
     * failed attempt (which may be retried), this releases the allocated
     * stock. $reason is required (unlike markFailed's, which predates this)
     * — recording nothing beyond "returned" made it too easy for a dispatcher
     * to lose track of why, and $scheduledDispatchAt lets the promise made to
     * the customer at return time ("redeliver tomorrow") get recorded
     * immediately rather than only at the eventual reassignment, so it's
     * visible on the board — and pre-filled at reassign time — in the
     * meantime.
     */
    public function markReturned(Order $order, string $reason, ?\DateTimeInterface $scheduledDispatchAt = null): void
    {
        DB::transaction(function () use ($order, $reason, $scheduledDispatchAt) {
            $this->fulfillment->releaseStock($order);

            $updates = [
                'delivery_status' => Order::DELIVERY_STATUS_RETURNED,
                'return_reason' => $reason,
            ];

            if ($scheduledDispatchAt !== null) {
                $updates['scheduled_dispatch_at'] = $scheduledDispatchAt;
            }

            $order->update($updates);

            $this->currentAttempt($order)?->update([
                'status' => Order::DELIVERY_STATUS_RETURNED,
                'completed_at' => now(),
                'return_reason' => $reason,
            ]);
        });
    }
}
