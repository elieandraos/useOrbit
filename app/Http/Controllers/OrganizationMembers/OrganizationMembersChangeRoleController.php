<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\ChangeOrganizationMemberRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMembers\ChangeOrganizationMemberRoleRequest;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationMembersChangeRoleController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('changeRole', [OrganizationMember::class, 'member'])]
    public function __invoke(ChangeOrganizationMemberRoleRequest $request, User $member, ChangeOrganizationMemberRoleAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $member, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return back();
    }
}
