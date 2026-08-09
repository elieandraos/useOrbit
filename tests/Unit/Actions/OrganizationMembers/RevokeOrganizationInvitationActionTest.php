<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\InviteOrganizationMemberAction;
use App\Actions\OrganizationMembers\RevokeOrganizationInvitationAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('hard-deletes the pending user row', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => 'invited',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RevokeOrganizationInvitationAction::class)->handle($invitee);

    expect(User::query()->find($invitee->id))->toBeNull();
});

test('removes the member from the organization roster', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => 'invited',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RevokeOrganizationInvitationAction::class)->handle($invitee);

    expect($organization->fresh()->users()->count())->toBe(0);
});

test('revoking an invitation frees the email for a new invite', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    setOrganizationContext($owner);

    /** @noinspection PhpUnhandledExceptionInspection */
    $invitee = app(InviteOrganizationMemberAction::class)->handle($owner, [
        'name' => 'Jane Doe',
        'email' => 'jane.doe@useorbit.com',
        'role' => 'member',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RevokeOrganizationInvitationAction::class)->handle($invitee);

    /** @noinspection PhpUnhandledExceptionInspection */
    $reinvited = app(InviteOrganizationMemberAction::class)->handle($owner, [
        'name' => 'Jane Doe',
        'email' => 'jane.doe@useorbit.com',
        'role' => 'member',
    ]);

    expect($reinvited->email)->toBe('jane.doe@useorbit.com');
});
