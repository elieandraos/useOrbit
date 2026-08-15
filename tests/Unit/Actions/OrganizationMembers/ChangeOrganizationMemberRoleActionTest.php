<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\ChangeOrganizationMemberRoleAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\MemberRoleChangedNotification;
use App\Notifications\YourRoleChangedNotification;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\Notification;

test('updates the member role', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'admin']);

    expect($member->fresh()->role)->toBe(OrganizationRole::Admin);
});

test('notifies leadership that the member role changed, excluding the actor, the affected member, and plain members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherAdmin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $plainMember = User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    $member = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'admin']);

    Notification::assertSentTo(
        $otherAdmin,
        MemberRoleChangedNotification::class,
        fn (MemberRoleChangedNotification $notification): bool => $notification->toArray($otherAdmin)['meta']['member']['id'] === $member->id
            && $notification->toArray($otherAdmin)['meta']['from_role'] === 'member'
            && $notification->toArray($otherAdmin)['meta']['to_role'] === 'admin',
    );
    Notification::assertNotSentTo($owner, MemberRoleChangedNotification::class);
    Notification::assertNotSentTo($member, MemberRoleChangedNotification::class);
    Notification::assertNotSentTo($plainMember, MemberRoleChangedNotification::class);
});

test('notifies the affected member personally that their role changed', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'admin']);

    Notification::assertSentTo(
        $member,
        YourRoleChangedNotification::class,
        fn (YourRoleChangedNotification $notification): bool => $notification->toArray($member)['meta']['from_role'] === 'member'
            && $notification->toArray($member)['meta']['to_role'] === 'admin',
    );
});

test('captures the previous role before the mutation', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    app(OrganizationContext::class)->set($organization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'member']);

    Notification::assertSentTo(
        $member,
        YourRoleChangedNotification::class,
        fn (YourRoleChangedNotification $notification): bool => $notification->toArray($member)['meta']['from_role'] === 'admin'
            && $notification->toArray($member)['meta']['to_role'] === 'member',
    );
});
