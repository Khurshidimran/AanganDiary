<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\RiderLocationPing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $rider = $request->user()->riderProfile;

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
        ]);

        $recordedAt = now();

        RiderLocationPing::create([
            'rider_id' => $rider->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy_meters' => $validated['accuracy_meters'] ?? null,
            'recorded_at' => $recordedAt,
        ]);

        $rider->update([
            'last_latitude' => $validated['latitude'],
            'last_longitude' => $validated['longitude'],
            'last_location_at' => $recordedAt,
        ]);

        return response()->json(['message' => 'Location recorded.']);
    }
}
