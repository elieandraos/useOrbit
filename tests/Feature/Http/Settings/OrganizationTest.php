<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('owner can view the organization settings page', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->get(route('organization.edit'))
        ->assertOk();
});

test('admin cannot view the organization settings page', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

test('member cannot view the organization settings page', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    $this->actingAs($member)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

test('owner can enable the organization two factor requirement', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => true])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($organization->fresh()->two_factor_required)->toBeTrue();
});

test('accepts the string "1" a browser form submission sends for the checked state', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => '1'])
        ->assertSessionHasNoErrors();

    expect($organization->fresh()->two_factor_required)->toBeTrue();
});

test('accepts the string "0" a browser form submission sends for the unchecked state', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => '0'])
        ->assertSessionHasNoErrors();

    expect($organization->fresh()->two_factor_required)->toBeFalse();
});

test('admin cannot update the organization two factor requirement', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)
        ->patch(route('organization.update'), ['two_factor_required' => true])
        ->assertForbidden();

    expect($organization->fresh()->two_factor_required)->toBeFalse();
});

test('updating the organization requires a boolean value', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $this->actingAs($owner)
        ->patch(route('organization.update'), ['two_factor_required' => 'not-a-boolean'])
        ->assertSessionHasErrors('two_factor_required');
});
