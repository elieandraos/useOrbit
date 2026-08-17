<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

test('org does not require two factor and user is not enrolled: passes through', function () {
    $organization = Organization::factory()->create(['two_factor_required' => false]);
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('org does not require two factor and user is enrolled: passes through', function () {
    $organization = Organization::factory()->create(['two_factor_required' => false]);
    $user = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('org requires two factor and user is enrolled: passes through', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('org requires two factor and user is not enrolled: redirected to enrollment', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'))
        ->assertSessionHas('error');
});

test('an unenrolled user in a requiring org may still reach the enrollment page itself', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk();
});

test('an unenrolled user in a requiring org may still confirm their password', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk();
});

test('an unenrolled user in a requiring org may still enable two factor authentication', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'))
        ->assertSessionHasNoErrors();
});

test('an unenrolled user in a requiring org may still logout', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
