<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;
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

        if (! $user->current_organization_id) {
            return redirect()->route('home')
                ->with('error', 'You are not associated with any organization.');
        }

        /** @var Organization|null $membership */
        $membership = $user->organizations()
            ->wherePivot('organization_id', $user->current_organization_id)
            ->first();

        if (! $membership || $membership->pivot->status !== OrganizationMemberStatus::Active) {
            return redirect()->route('home')
                ->with('error', 'Your membership in this organization is not active.');
        }

        return $next($request);
    }
}
