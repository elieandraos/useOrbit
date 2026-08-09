<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $organization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    $this->delete(route('organization-members.revoke-invitation', $invitee))
        ->assertRedirect(route('login'));
});

test('member cannot revoke a pending invitation', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    $this->actingAs($user)
        ->delete(route('organization-members.revoke-invitation', $invitee))
        ->assertForbidden();
});

test('owner cannot revoke an active member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.revoke-invitation', $member))
        ->assertForbidden();
});

test('revoke-invitation redirects with a success toast on the happy path', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    $this->actingAs($owner)
        ->delete(route('organization-members.revoke-invitation', $invitee))
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Invitation revoked.');

    expect(User::query()->find($invitee->id))->toBeNull();
});
