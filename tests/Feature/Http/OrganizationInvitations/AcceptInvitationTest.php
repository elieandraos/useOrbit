<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Actions\OrganizationMembers\RevokeOrganizationInvitationAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;

function inviteMemberAndCaptureToken(User $owner): array
{
    Notification::fake();
    setOrganizationContext($owner);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($owner, [
        'name' => 'Jane Doe',
        'email' => 'jane.doe@useorbit.com',
        'role' => 'member',
    ]);

    $token = null;
    Notification::assertSentTo(
        $invitee,
        OrganizationInvitationNotification::class,
        function (OrganizationInvitationNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        },
    );

    return [$invitee, $token];
}

test('renders the accept-invitation page for a valid token', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = inviteMemberAndCaptureToken($owner);

    $this->get(route('invitations.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->where('organization', 'Acme Insurance')
            ->where('email', $invitee->email)
        );
});

test('renders the invalid page for an unknown token', function () {
    $this->get(route('invitations.show', 'not-a-real-token'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/InvitationInvalid'));
});

test('renders the invalid page for an expired token', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = inviteMemberAndCaptureToken($owner);

    $invitee->update(['invitation_expires_at' => now()->subDay()]);

    $this->get(route('invitations.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/InvitationInvalid'));
});

test('renders the invalid page for an already-accepted token', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = inviteMemberAndCaptureToken($owner);

    $invitee->update([
        'status' => OrganizationMemberStatus::Active,
        'joined_at' => now(),
        'invitation_token' => null,
        'invitation_expires_at' => null,
    ]);

    $this->get(route('invitations.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/InvitationInvalid'));
});

test('renders the invalid page once the invitation has been revoked', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = inviteMemberAndCaptureToken($owner);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RevokeOrganizationInvitationAction::class)->handle($invitee);

    $this->get(route('invitations.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/InvitationInvalid'));
});

test('renders the accept-invitation page with no inviter once the inviter has been deleted', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = inviteMemberAndCaptureToken($owner);

    $owner->delete();

    $this->get(route('invitations.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->where('invitedBy', null)
        );
});

test('authenticated users are redirected away from the show page', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('invitations.show', 'any-token'))
        ->assertRedirect(route('dashboard'));
});

test('authenticated users are redirected away from the store endpoint', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('invitations.store', 'any-token'), [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('dashboard'));
});

test('password is required to accept an invitation', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [, $token] = inviteMemberAndCaptureToken($owner);

    $this->post(route('invitations.store', $token), [])
        ->assertInvalid(['password']);
});

test('an invalid token redirects back to the show page on submit', function () {
    $this->post(route('invitations.store', 'not-a-real-token'), [
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ])->assertRedirect(route('invitations.show', 'not-a-real-token'));
});

test('accepting an invitation logs the invitee in and redirects to the dashboard', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = inviteMemberAndCaptureToken($owner);

    $this->post(route('invitations.store', $token), [
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($invitee->fresh());
});
