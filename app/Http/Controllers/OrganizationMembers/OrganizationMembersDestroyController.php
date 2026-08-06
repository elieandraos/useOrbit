<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationMembers;

use App\Actions\OrganizationMembers\RemoveOrganizationMemberAction;
use App\Http\Controllers\Controller;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationMembersDestroyController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('remove', [OrganizationMember::class, 'member'])]
    public function __invoke(Request $request, User $member, RemoveOrganizationMemberAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $member);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return back();
    }
}
