<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\AcceptOrganizationInvitationAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

$makeInvitee = function (Organization $organization, array $overrides = []): User {
    return User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
        'invitation_token' => hash('sha256', 'some-token'),
        'invitation_expires_at' => now()->addDays(7),
        ...$overrides,
    ]);
};

test('sets the password and hashes it', function () use ($makeInvitee) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = $makeInvitee($organization);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitee, ['password' => 'a-strong-password']);

    expect(Hash::check('a-strong-password', $invitee->fresh()->password))->toBeTrue();
});

test('activates the membership and clears the token and expiry', function () use ($makeInvitee) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = $makeInvitee($organization);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitee, ['password' => 'a-strong-password']);

    $fresh = $invitee->fresh();

    expect($fresh->status)->toBe(OrganizationMemberStatus::Active)
        ->and($fresh->invitation_token)->toBeNull()
        ->and($fresh->invitation_expires_at)->toBeNull()
        ->and($fresh->joined_at)->not->toBeNull();
});

test('notifies the organization owner that the member joined', function () use ($makeInvitee) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = $makeInvitee($organization, ['name' => 'Jane Doe']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(AcceptOrganizationInvitationAction::class)->handle($invitee, ['password' => 'a-strong-password']);

    Notification::assertSentTo(
        $owner,
        MemberJoinedNotification::class,
        fn (MemberJoinedNotification $notification): bool => $notification->toArray($owner)['meta']['member']['id'] === $invitee->id,
    );
});

test('a second acceptance of the same invitation is rejected without mutating the first result', function () use ($makeInvitee) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $invitee = $makeInvitee($organization);

    /** @noinspection PhpUnhandledExceptionInspection */
    $firstAccept = app(AcceptOrganizationInvitationAction::class)->handle($invitee, ['password' => 'first-strong-password']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $secondAccept = app(AcceptOrganizationInvitationAction::class)->handle($invitee, ['password' => 'second-strong-password']);

    expect($firstAccept)->not->toBeNull()
        ->and($secondAccept)->toBeNull()
        ->and(Hash::check('first-strong-password', $invitee->fresh()->password))->toBeTrue();
});
