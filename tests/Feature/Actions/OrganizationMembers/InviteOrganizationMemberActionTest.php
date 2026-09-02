<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\Notification;

$attributes = [
    'name' => 'Jane Doe',
    'email' => 'jane.doe@useorbit.com',
    'role' => 'member',
];

test('creates a pending user with no password', function () use ($attributes) {
    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    setOrganizationContext($inviter);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    expect($invitee->name)->toBe('Jane Doe')
        ->and($invitee->email)->toBe('jane.doe@useorbit.com')
        ->and($invitee->password)->toBeNull();
});

test('creates an invited member scoped to the inviter organization', function () use ($attributes) {
    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    setOrganizationContext($inviter);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, [...$attributes, 'role' => 'admin']);

    expect($invitee->organization_id)->toBe($organization->id)
        ->and($invitee->role)->toBe(OrganizationRole::Admin)
        ->and($invitee->status)->toBe(OrganizationMemberStatus::Invited)
        ->and($invitee->invited_by)->toBe($inviter->id)
        ->and($invitee->invitation_expires_at->isBetween(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()))->toBeTrue();
});

test('scopes the invited member to the organization context rather than the inviter organization', function () use ($attributes) {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    app(OrganizationContext::class)->set($otherOrganization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    expect($invitee->organization_id)->toBe($otherOrganization->id)
        ->and($invitee->organization_id)->not->toBe($inviter->organization_id)
        ->and($invitee->invited_by)->toBe($inviter->id);
});

test('stores a hashed token that matches the plaintext token sent in the notification', function () use ($attributes) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    setOrganizationContext($inviter);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    Notification::assertSentTo(
        $invitee,
        OrganizationInvitationNotification::class,
        fn (OrganizationInvitationNotification $notification): bool => hash('sha256', $notification->token) === $invitee->invitation_token,
    );
});

test('dispatches the invitation notification to the invitee', function () use ($attributes) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    setOrganizationContext($inviter);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($inviter, $attributes);

    Notification::assertSentTo(
        $invitee,
        OrganizationInvitationNotification::class,
        fn (OrganizationInvitationNotification $notification): bool => $notification->organization->is($organization)
            && $notification->invitedBy->is($inviter),
    );
});
