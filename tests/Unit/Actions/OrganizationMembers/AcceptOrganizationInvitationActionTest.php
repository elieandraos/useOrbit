<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\AcceptOrganizationInvitationAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

$attachInvitation = function (Organization $organization, User $invitee): OrganizationMember {
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
        'token' => hash('sha256', 'some-token'),
        'expires_at' => now()->addDays(7),
    ]);

    /** @var OrganizationMember $invitation */
    $invitation = OrganizationMember::query()
        ->where('user_id', $invitee->id)
        ->where('organization_id', $organization->id)
        ->firstOrFail();

    return $invitation;
};

test('sets the password and hashes it', function () use ($attachInvitation) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitation = $attachInvitation($organization, $invitee);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitation, ['password' => 'a-strong-password']);

    expect(Hash::check('a-strong-password', $invitee->fresh()->password))->toBeTrue();
});

test('activates the pivot and clears the token and expiry', function () use ($attachInvitation) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitation = $attachInvitation($organization, $invitee);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitation, ['password' => 'a-strong-password']);

    $pivot = $invitation->fresh();

    expect($pivot->status)->toBe(OrganizationMemberStatus::Active)
        ->and($pivot->token)->toBeNull()
        ->and($pivot->expires_at)->toBeNull()
        ->and($pivot->joined_at)->not->toBeNull();
});

test('sets the current organization on the invitee', function () use ($attachInvitation) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitation = $attachInvitation($organization, $invitee);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitation, ['password' => 'a-strong-password']);

    expect($invitee->fresh()->current_organization_id)->toBe($organization->id);
});

test('notifies the organization owner that the member joined', function () use ($attachInvitation) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->create(['password' => null, 'name' => 'Jane Doe']);
    $invitation = $attachInvitation($organization, $invitee);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitation, ['password' => 'a-strong-password']);

    Notification::assertSentTo(
        $owner,
        MemberJoinedNotification::class,
        fn (MemberJoinedNotification $notification): bool => $notification->toArray($owner)['meta']['member']['id'] === $invitee->id,
    );
});

test('a second acceptance of the same invitation is rejected without mutating the first result', function () use ($attachInvitation) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitation = $attachInvitation($organization, $invitee);

    /** @noinspection PhpUnhandledExceptionInspection */
    $firstAccept = app(AcceptOrganizationInvitationAction::class)->handle($invitation, ['password' => 'first-strong-password']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $secondAccept = app(AcceptOrganizationInvitationAction::class)->handle($invitation, ['password' => 'second-strong-password']);

    expect($firstAccept)->not->toBeNull()
        ->and($secondAccept)->toBeNull()
        ->and(Hash::check('first-strong-password', $invitee->fresh()->password))->toBeTrue();
});
