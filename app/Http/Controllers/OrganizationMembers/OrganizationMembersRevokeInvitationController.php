<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\RevokeOrganizationInvitationAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationMembersRevokeInvitationController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('revoke', [User::class, 'member'])]
    public function __invoke(User $member, RevokeOrganizationInvitationAction $action): RedirectResponse
    {
        $action->handle($member);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return back();
    }
}
