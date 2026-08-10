<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $rider = $user->riderProfile;

        if (! $rider) {
            throw ValidationException::withMessages([
                'email' => ['This account is not registered as a rider.'],
            ]);
        }

        if ($rider->status === RiderProfile::STATUS_INACTIVE) {
            throw ValidationException::withMessages([
                'email' => ['This rider account is inactive. Contact your dispatcher.'],
            ]);
        }

        $token = $user->createToken('rider-app', ['rider'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'rider' => [
                'id' => $rider->id,
                'name' => $user->name,
                'phone' => $rider->phone,
                'zone' => $rider->zone,
                'status' => $rider->status,
                'wallet_balance' => (float) $rider->wallet_balance,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
