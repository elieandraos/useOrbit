<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMembers\InviteOrganizationMemberRequest;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationMembersController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('invite', OrganizationMember::class)]
    public function store(InviteOrganizationMemberRequest $request, InviteOrganizationMemberAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return back();
    }
}
