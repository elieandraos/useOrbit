<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\ResetTwoFactorAuthenticationAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationMembersResetTwoFactorController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('resetTwoFactor', [User::class, 'member'])]
    public function __invoke(Request $request, User $member, ResetTwoFactorAuthenticationAction $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $action->handle($actor, $member);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Two-factor authentication reset.')]);

        return back();
    }
}
