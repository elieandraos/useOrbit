<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\OrganizationMemberStatus;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOrganizationContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->status !== OrganizationMemberStatus::Active) {
            return redirect()->route('home')
                ->with('error', 'Your membership in this organization is not active.');
        }

        app(OrganizationContext::class)->set($user->organization_id);

        return $next($request);
    }
}
