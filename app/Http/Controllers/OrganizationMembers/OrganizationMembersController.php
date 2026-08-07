<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Actions\OrganizationMembers\RemoveOrganizationMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMembers\InviteOrganizationMemberRequest;
use App\Http\Requests\OrganizationMembers\RemoveOrganizationMemberRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationMembersController extends Controller
{
    #[Authorize('viewAny', OrganizationMember::class)]
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $members = $user->currentOrganization
            ->users()
            ->orderBy('users.name')
            ->get();

        return response()->json([
            'data' => OrganizationMemberResource::collection($members)->resolve($request),
        ]);
    }

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

    /**
     * @throws \Throwable
     */
    #[Authorize('remove', [OrganizationMember::class, 'member'])]
    public function destroy(RemoveOrganizationMemberRequest $request, User $member, RemoveOrganizationMemberAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var User $successor */
        $successor = User::query()->findOrFail($request->validated('reassign_to'));

        $action->handle($user, $member, $successor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return back();
    }
}
