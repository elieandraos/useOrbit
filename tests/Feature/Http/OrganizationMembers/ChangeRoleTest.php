<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $member = User::factory()->withOrganization()->create();

    $this->patch(route('organization-members.change-role', $member), ['role' => 'admin'])
        ->assertRedirect(route('login'));
});

test('member cannot change another member role', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->patch(route('organization-members.change-role', $member), ['role' => 'admin'])
        ->assertForbidden();
});

test('owner cannot change their own role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->patch(route('organization-members.change-role', $owner), ['role' => 'admin'])
        ->assertForbidden();
});

test('change-role returns a validation error when role is missing', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->patch(route('organization-members.change-role', $member))
        ->assertSessionHasErrors(['role']);
});

test('change-role rejects a role outside admin or member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->patch(route('organization-members.change-role', $member), ['role' => 'owner'])
        ->assertSessionHasErrors(['role']);
});

test('change-role redirects with a success toast on the happy path', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->patch(route('organization-members.change-role', $member), ['role' => 'admin'])
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Member role updated.');

    expect($member->fresh()->role)->toBe(OrganizationRole::Admin);
});
