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
        ->assertHasInertiaFlash('error', 'Your organization requires two-factor authentication. Please finish setting it up to continue.');
});

test('org requires two factor and user is not enrolled: a background JSON request gets a 423 instead of being redirected', function () {
    $organization = Organization::factory()->create(['two_factor_required' => true]);
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->getJson(route('notifications.recent'))
        ->assertStatus(423)
        ->assertJson(['message' => 'Your organization requires two-factor authentication. Please finish setting it up to continue.']);
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
