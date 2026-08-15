<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Actions\OrganizationMembers\RemoveOrganizationMemberAction;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMembers\InviteOrganizationMemberRequest;
use App\Http\Requests\OrganizationMembers\RemoveOrganizationMemberRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationMembersController extends Controller
{
    #[Authorize('manage', User::class)]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $members = $user->organization
            ->users()
            ->orderBy('users.name')
            ->get();

        return inertia('OrganizationMembers/Index', [
            'members' => OrganizationMemberResource::collection($members),
            'roleOptions' => OrganizationRole::invitableOptions(),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('invite', User::class)]
    public function store(InviteOrganizationMemberRequest $request, InviteOrganizationMemberAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return back();
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('remove', [User::class, 'member'])]
    public function destroy(RemoveOrganizationMemberRequest $request, User $member, RemoveOrganizationMemberAction $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        /** @var User $successor */
        $successor = User::query()->findOrFail($request->validated('reassign_to'));

        $action->handle($actor, $member, $successor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return back();
    }
}
