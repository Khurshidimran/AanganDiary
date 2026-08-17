<?php

namespace App\Http\Middleware;

use App\Models\RiderProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes the rider API to actual riders — checked via the rider_profiles
 * relation rather than just the 'Rider' role, since the profile row is what
 * everything else (deliveries, wallet) actually joins against.
 *
 * Status is checked here (every request), not just at login — a rider
 * deactivated mid-shift loses API access immediately rather than keeping
 * their already-issued token valid until they next try to log in.
 */
class EnsureIsRider
{
    public function handle(Request $request, Closure $next): Response
    {
        $rider = $request->user()?->riderProfile;

        if (! $rider) {
            abort(403, 'This account is not a rider.');
        }

        if ($rider->status !== RiderProfile::STATUS_ACTIVE) {
            abort(403, 'This rider account is inactive. Contact your dispatcher.');
        }

        return $next($request);
    }
}
