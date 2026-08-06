<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $member = User::factory()->withOrganization()->create();

    $this->delete(route('organization-members.destroy', $member))
        ->assertRedirect(route('login'));
});

test('member cannot remove another member', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->delete(route('organization-members.destroy', $member))
        ->assertForbidden();
});

test('owner cannot remove themselves', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $owner))
        ->assertForbidden();
});

test('destroy redirects with a success toast on the happy path', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $member))
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Member removed.');
});
