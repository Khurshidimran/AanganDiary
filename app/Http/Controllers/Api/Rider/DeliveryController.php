<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\DispatchService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class DeliveryController extends Controller
{
    public function __construct(private readonly DispatchService $dispatch)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $rider = $request->user()->riderProfile;

        $orders = Order::with('items')
            ->where('rider_id', $rider->id)
            ->when($request->filled('status'), fn ($q) => $q->where('delivery_status', $request->query('status')))
            ->latest('assigned_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->ensureOwnership($request, $order);

        return new OrderResource($order->load('items'));
    }

    public function pickedUp(Request $request, Order $order): OrderResource
    {
        $this->ensureOwnership($request, $order);
        abort_unless($order->canBeMarkedPickedUp(), 422, 'This order is not in an assigned state.');

        $this->dispatch->markPickedUp($order);

        return new OrderResource($order->fresh('items'));
    }

    public function outForDelivery(Request $request, Order $order): OrderResource
    {
        $this->ensureOwnership($request, $order);
        abort_unless($order->canBeMarkedOutForDelivery(), 422, 'This order has not been picked up yet.');

        $this->dispatch->markOutForDelivery($order);

        return new OrderResource($order->fresh('items'));
    }

    public function bulkPickedUp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['string'],
        ]);

        return $this->bulkTransition($request, $validated['order_ids'], fn (Collection $orders) => $this->dispatch->markManyPickedUp($orders));
    }

    public function bulkOutForDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['string'],
        ]);

        return $this->bulkTransition($request, $validated['order_ids'], fn (Collection $orders) => $this->dispatch->markManyOutForDelivery($orders));
    }

    public function delivered(Request $request, Order $order): OrderResource
    {
        $this->ensureOwnership($request, $order);
        abort_unless($order->canBeMarkedDelivered(), 422, 'This order is not out for delivery.');

        $validated = $request->validate([
            'photo' => ['nullable', 'required_without:signature', 'image', 'max:5120'],
            'signature' => ['nullable', 'required_without:photo', 'image', 'max:2048'],
        ]);

        $directory = "proof-of-delivery/{$order->id}";
        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store($directory, 'public') : null;
        $signaturePath = $request->hasFile('signature') ? $request->file('signature')->store($directory, 'public') : null;

        $this->dispatch->markDelivered($order, $photoPath, $signaturePath);

        return new OrderResource($order->fresh('items'));
    }

    public function failed(Request $request, Order $order): OrderResource
    {
        $this->ensureOwnership($request, $order);
        abort_unless($order->canBeMarkedFailedOrReturned(), 422, 'This order cannot be marked as failed right now.');

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->dispatch->markFailed($order, $validated['reason']);

        return new OrderResource($order->fresh('items'));
    }

    public function returned(Request $request, Order $order): OrderResource
    {
        $this->ensureOwnership($request, $order);
        abort_unless($order->canBeMarkedFailedOrReturned(), 422, 'This order cannot be marked as returned right now.');

        $this->dispatch->markReturned($order);

        return new OrderResource($order->fresh('items'));
    }

    /**
     * Records the rider's chosen delivery order — purely a planning/display
     * sequence (route_sequence), independent of delivery_status. Doesn't
     * transition any order's status; the rider still marks each one picked
     * up / out for delivery individually as they physically work through it.
     */
    public function checkout(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['string'],
        ]);

        $orders = Order::where('rider_id', $rider->id)
            ->whereIn('id', $validated['order_ids'])
            ->get()
            ->keyBy('id');

        if ($orders->count() !== count($validated['order_ids'])) {
            abort(422, 'One or more orders are not assigned to you.');
        }

        foreach (array_values($validated['order_ids']) as $index => $orderId) {
            $orders->get($orderId)->update(['route_sequence' => $index + 1]);
        }

        $orders->load('items');
        $route = $orders->sortBy('route_sequence')->values();

        return response()->json([
            'route' => OrderResource::collection($route),
            'start_order' => new OrderResource($route->first()),
        ]);
    }

    private function ensureOwnership(Request $request, Order $order): void
    {
        abort_unless(
            $order->rider_id === $request->user()->riderProfile->id,
            403,
            'This delivery is not assigned to you.',
        );
    }

    /**
     * Partitions requested ids into ones this rider actually owns (passed on
     * to the given DispatchService bulk transition) vs. ones that don't
     * exist or belong to someone else (reported as skipped immediately,
     * without ever reaching the state-eligibility check) — then merges both
     * skip lists into one response.
     *
     * @param  list<string>  $requestedIds
     */
    private function bulkTransition(Request $request, array $requestedIds, Closure $transition): JsonResponse
    {
        $riderId = $request->user()->riderProfile->id;
        $found = Order::whereIn('id', $requestedIds)->get()->keyBy('id');

        $owned = collect();
        $skipped = [];

        foreach ($requestedIds as $id) {
            $order = $found->get($id);

            if (! $order || $order->rider_id !== $riderId) {
                $skipped[] = ['id' => $id, 'reason' => 'not found or not assigned to you'];

                continue;
            }

            $owned->push($order);
        }

        $result = $transition($owned);

        foreach ($result['skipped'] as $entry) {
            $skipped[] = ['id' => $entry['order']->id, 'reason' => $entry['reason']];
        }

        return response()->json([
            'succeeded' => OrderResource::collection(collect($result['succeeded'])->each->load('items')),
            'skipped' => $skipped,
        ]);
    }
}
