<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['fcm_token' => ['required', 'string', 'max:255']]);

        $request->user()->riderProfile->update(['fcm_token' => $validated['fcm_token']]);

        return response()->json(['message' => 'Device token registered.']);
    }
}
