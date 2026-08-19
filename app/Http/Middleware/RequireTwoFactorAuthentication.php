<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class RequireTwoFactorAuthentication
{
    /**
     * The enrollment route itself must stay reachable, or an unenrolled user
     * would be redirected back into an infinite loop. Fortify's own
     * password-confirmation and two-factor-management routes never carry
     * the `organization` middleware group, so they don't need allowlisting
     * here — this middleware structurally never runs on them.
     */
    private const string ENROLLMENT_ROUTE = 'security.edit';

    private const string REQUIREMENT_MESSAGE = 'Your organization requires two-factor authentication. Please finish setting it up to continue.';

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

        if ($request->expectsJson()) {
            return response()->json(['message' => self::REQUIREMENT_MESSAGE], 423);
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => self::REQUIREMENT_MESSAGE]);

        return redirect()->route(self::ENROLLMENT_ROUTE);
    }
}
