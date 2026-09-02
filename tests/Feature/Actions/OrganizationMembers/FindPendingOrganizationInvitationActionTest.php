<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\FindPendingOrganizationInvitationAction;
use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;

$invite = function (User $inviter, string $email = 'jane.doe@useorbit.com'): array {
    Notification::fake();
    setOrganizationContext($inviter);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, [
        'name' => 'Jane Doe',
        'email' => $email,
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
};

test('resolves the invitation for a valid, unexpired token', function () use ($invite) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = $invite($owner);

    $found = app(FindPendingOrganizationInvitationAction::class)->handle($token);

    expect($found?->is($invitee))->toBeTrue()
        ->and($found?->organization->is($organization))->toBeTrue()
        ->and($found?->inviter->is($owner))->toBeTrue();
});

test('eager loads the organization and inviter relations', function () use ($invite) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [, $token] = $invite($owner);

    $found = app(FindPendingOrganizationInvitationAction::class)->handle($token);

    expect($found?->relationLoaded('organization'))->toBeTrue()
        ->and($found?->relationLoaded('inviter'))->toBeTrue();
});

test('returns null for a token that matches no pending invitation', function () {
    $found = app(FindPendingOrganizationInvitationAction::class)->handle('not-a-real-token');

    expect($found)->toBeNull();
});

test('returns null once the invitation has expired', function () use ($invite) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = $invite($owner);

    $invitee->update(['invitation_expires_at' => now()->subDay()]);

    $found = app(FindPendingOrganizationInvitationAction::class)->handle($token);

    expect($found)->toBeNull();
});

test('returns null once the invitation has already been accepted', function () use ($invite) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [$invitee, $token] = $invite($owner);

    $invitee->update([
        'status' => OrganizationMemberStatus::Active,
        'joined_at' => now(),
        'invitation_token' => null,
        'invitation_expires_at' => null,
    ]);

    $found = app(FindPendingOrganizationInvitationAction::class)->handle($token);

    expect($found)->toBeNull();
});

test('does not match a token belonging to a different invitation', function () use ($invite) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    [, $tokenA] = $invite($owner, 'jane.doe@useorbit.com');
    [$inviteeB] = $invite($owner, 'john.smith@useorbit.com');

    $found = app(FindPendingOrganizationInvitationAction::class)->handle($tokenA);

    expect($found?->is($inviteeB))->toBeFalse();
});
