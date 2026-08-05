<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

$attributes = [
    'name' => 'Jane Doe',
    'email' => 'jane.doe@useorbit.com',
    'role' => 'member',
];

test('creates a pending user with no password', function () use ($attributes) {
    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    expect($invitee->name)->toBe('Jane Doe')
        ->and($invitee->email)->toBe('jane.doe@useorbit.com')
        ->and($invitee->password)->toBeNull();
});

test('attaches an invited pivot scoped to the inviter organization', function () use ($attributes) {
    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, [...$attributes, 'role' => 'admin']);

    $pivot = $invitee->organizations()->wherePivot('organization_id', $organization->id)->first()?->pivot;

    expect($pivot->role)->toBe(OrganizationRole::Admin)
        ->and($pivot->status)->toBe(OrganizationMemberStatus::Invited)
        ->and($pivot->invited_by)->toBe($inviter->id)
        ->and($pivot->expires_at->isBetween(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()))->toBeTrue();
});

test('stores a hashed token that matches the plaintext token sent in the notification', function () use ($attributes) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    $pivot = $invitee->organizations()->wherePivot('organization_id', $organization->id)->first()?->pivot;

    Notification::assertSentTo(
        $invitee,
        OrganizationInvitationNotification::class,
        fn (OrganizationInvitationNotification $notification): bool => Hash::check($notification->token, $pivot->token),
    );
});

test('dispatches the invitation notification to the invitee', function () use ($attributes) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    Notification::assertSentTo(
        $invitee,
        OrganizationInvitationNotification::class,
        fn (OrganizationInvitationNotification $notification): bool => $notification->organization->is($organization)
            && $notification->invitedBy->is($inviter),
    );
});
