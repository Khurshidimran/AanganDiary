<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Services\RiderTripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Rider-profile-level status: the simple online/offline availability switch,
 * location-verified warehouse check-in/out (governs dispatch-board
 * visibility — see DispatchController::index()), and the main-screen
 * dashboard summary. Deliberately separate concepts: a rider can be online
 * without being checked in.
 */
class RiderStatusController extends Controller
{
    public function __construct(private readonly RiderTripService $trips)
    {
    }

    public function online(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;
        $rider->update(['is_online' => true]);

        return response()->json(['is_online' => true]);
    }

    public function offline(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;
        $rider->update(['is_online' => false]);

        return response()->json(['is_online' => false]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if (! $rider->warehouse_id) {
            abort(422, 'You have no warehouse assigned — contact your dispatcher.');
        }

        $distance = $rider->distanceToWarehouseMeters($validated['latitude'], $validated['longitude']);

        if ($distance === null) {
            abort(422, 'Your warehouse has no location configured — contact your dispatcher.');
        }

        if ($distance > RiderProfile::CHECK_IN_RADIUS_METERS) {
            abort(422, sprintf(
                'You need to be within %dm of your warehouse to check in — you are about %dm away.',
                RiderProfile::CHECK_IN_RADIUS_METERS,
                $distance,
            ));
        }

        $rider->update([
            'is_checked_in' => true,
            'checked_in_at' => now(),
            'checked_in_latitude' => $validated['latitude'],
            'checked_in_longitude' => $validated['longitude'],
        ]);

        $this->trips->openTrip($rider, $validated['latitude'], $validated['longitude']);

        return response()->json([
            'is_checked_in' => true,
            'checked_in_at' => $rider->checked_in_at->toIso8601String(),
            'distance_meters' => round($distance, 1),
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;
        $rider->update(['is_checked_in' => false]);
        $this->trips->closeTrip($rider);

        return response()->json(['is_checked_in' => false]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;

        $assignedOrdersCount = Order::where('rider_id', $rider->id)
            ->where('delivery_status', Order::DELIVERY_STATUS_ASSIGNED)
            ->count();

        $doneTodayCount = Order::where('rider_id', $rider->id)
            ->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)
            ->whereDate('delivered_at', today())
            ->count();

        return response()->json([
            'is_online' => $rider->is_online,
            'is_checked_in' => $rider->is_checked_in,
            'wallet_balance' => (float) $rider->wallet_balance,
            'assigned_orders_count' => $assignedOrdersCount,
            'done_today_count' => $doneTodayCount,
        ]);
    }
}
