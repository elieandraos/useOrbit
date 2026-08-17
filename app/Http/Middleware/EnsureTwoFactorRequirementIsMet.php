<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTwoFactorRequirementIsMet
{
    /**
     * The enrollment route itself must stay reachable, or an unenrolled user
     * would be redirected back into an infinite loop. Fortify's own
     * password-confirmation and two-factor-management routes never carry
     * the `organization` middleware group, so they don't need allowlisting
     * here — this middleware structurally never runs on them.
     */
    private const string ENROLLMENT_ROUTE = 'security.edit';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->organization->two_factor_required) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->routeIs(self::ENROLLMENT_ROUTE)) {
            return $next($request);
        }

        return redirect()->route(self::ENROLLMENT_ROUTE)
            ->with('error', 'Your organization requires two-factor authentication. Please finish setting it up to continue.');
    }
}
