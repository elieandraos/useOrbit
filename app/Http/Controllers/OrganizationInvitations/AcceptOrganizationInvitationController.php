<?php

declare(strict_types=1);

namespace App\Http\Controllers\OrganizationInvitations;

use App\Actions\OrganizationMembers\AcceptOrganizationInvitationAction;
use App\Actions\OrganizationMembers\FindPendingOrganizationInvitationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationInvitations\AcceptOrganizationInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class AcceptOrganizationInvitationController extends Controller
{
    public function show(string $token, FindPendingOrganizationInvitationAction $find): Response
    {
        $invitation = $find->handle($token);

        if ($invitation === null) {
            return Inertia::render('auth/InvitationInvalid');
        }

        return Inertia::render('auth/AcceptInvitation', [
            'token' => $token,
            'organization' => $invitation->organization->name,
            'invitedBy' => $invitation->inviter?->name,
            'role' => $invitation->role->label(),
            'name' => $invitation->name,
            'email' => $invitation->email,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function store(
        string $token,
        AcceptOrganizationInvitationRequest $request,
        FindPendingOrganizationInvitationAction $find,
        AcceptOrganizationInvitationAction $accept,
    ): RedirectResponse {
        $invitation = $find->handle($token);

        if ($invitation === null) {
            return to_route('invitations.show', $token);
        }

        $accepted = $accept->handle($invitation, $request->validated());

        if ($accepted === null) {
            return to_route('invitations.show', $token);
        }

        Auth::login($accepted);
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
