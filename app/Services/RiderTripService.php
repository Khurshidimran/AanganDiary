<?php

namespace App\Services;

use App\Models\RiderProfile;
use App\Models\RiderTrip;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * Opens/closes rider "trip" (check-in-to-check-out) sessions. Deliberately
 * separate from RiderProfile.is_checked_in — that flag is the live
 * dispatch-board-visibility switch (see DispatchController::index()) and is
 * flipped in place by RiderController/Api\Rider\RiderStatusController; this
 * service just layers a history record on top so past sessions survive the
 * next check-out.
 */
class RiderTripService
{
    /**
     * Idempotent — a rider who's already got an open trip just gets it back,
     * rather than a second one spawning from a double check-in.
     */
    public function openTrip(RiderProfile $rider, ?float $latitude = null, ?float $longitude = null, ?DateTimeInterface $at = null): RiderTrip
    {
        $existing = $this->currentOpenTrip($rider);

        if ($existing) {
            return $existing;
        }

        return RiderTrip::create([
            'rider_id' => $rider->id,
            'warehouse_id' => $rider->warehouse_id,
            'checked_in_at' => $at ?? now(),
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'status' => RiderTrip::STATUS_ACTIVE,
        ]);
    }

    /**
     * No-op (returns null) if the rider has no open trip — a legacy rider or
     * one who's never gone through openTrip() simply has no session to
     * close, which isn't an error.
     */
    public function closeTrip(RiderProfile $rider, ?float $latitude = null, ?float $longitude = null, ?DateTimeInterface $at = null): ?RiderTrip
    {
        $trip = $this->currentOpenTrip($rider);

        if (! $trip) {
            return null;
        }

        $trip->update([
            'checked_out_at' => $at ?? now(),
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'status' => RiderTrip::STATUS_COMPLETED,
        ]);

        return $trip;
    }

    public function currentOpenTrip(?RiderProfile $rider): ?RiderTrip
    {
        if (! $rider) {
            return null;
        }

        return RiderTrip::where('rider_id', $rider->id)
            ->where('status', RiderTrip::STATUS_ACTIVE)
            ->latest('checked_in_at')
            ->first();
    }
}
