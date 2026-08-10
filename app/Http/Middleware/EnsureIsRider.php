<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes the rider API to actual riders — checked via the rider_profiles
 * relation rather than just the 'Rider' role, since the profile row is what
 * everything else (deliveries, wallet) actually joins against.
 */
class EnsureIsRider
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->riderProfile) {
            abort(403, 'This account is not a rider.');
        }

        return $next($request);
    }
}
