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
    $successor = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->delete(route('organization-members.destroy', $member), ['reassign_to' => $successor->id])
        ->assertForbidden();
});

test('owner cannot remove themselves', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $successor = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $owner), ['reassign_to' => $successor->id])
        ->assertForbidden();
});

test('destroy requires reassign_to', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $member))
        ->assertSessionHasErrors(['reassign_to']);
});

test('destroy rejects a reassign_to that is not an active member of the organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $outsider = User::factory()->withOrganization()->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $member), ['reassign_to' => $outsider->id])
        ->assertSessionHasErrors(['reassign_to']);
});

test('destroy rejects a reassign_to equal to the member being removed', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $member), ['reassign_to' => $member->id])
        ->assertSessionHasErrors(['reassign_to']);
});

test('destroy allows the actor to reassign to themselves', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $member), ['reassign_to' => $owner->id])
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Member removed.');
});

test('destroy redirects with a success toast on the happy path', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $successor = User::factory()->forOrganization($organization)->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.destroy', $member), ['reassign_to' => $successor->id])
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Member removed.');

    $this->assertModelMissing($member);
});
