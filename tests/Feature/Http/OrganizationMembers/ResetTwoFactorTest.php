<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $member = User::factory()->withOrganization()->create();

    $this->delete(route('organization-members.reset-two-factor', $member))
        ->assertRedirect(route('login'));
});

test('member cannot reset another member two-factor authentication', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $member = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('organization-members.reset-two-factor', $member))
        ->assertForbidden();
});

test('admin cannot reset an owner two-factor authentication', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('organization-members.reset-two-factor', $owner))
        ->assertForbidden();
});

test('reset-two-factor requires password confirmation', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    $this->actingAs($owner)
        ->delete(route('organization-members.reset-two-factor', $member))
        ->assertRedirect(route('password.confirm'));

    expect($member->fresh()->two_factor_secret)->not->toBeNull();
});

test('reset-two-factor redirects with a success toast on the happy path', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    $this->actingAs($owner)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('organization-members.reset-two-factor', $member))
        ->assertRedirect()
        ->assertHasInertiaFlash('success', 'Two-factor authentication reset.');

    expect($member->fresh()->two_factor_secret)->toBeNull();
});
