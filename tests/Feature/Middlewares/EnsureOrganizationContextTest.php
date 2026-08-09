<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('authenticated user with suspended membership is redirected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create([
        'status' => OrganizationMemberStatus::Suspended,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Your membership in this organization is not active.');
});

test('authenticated user with invited membership is redirected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create([
        'status' => OrganizationMemberStatus::Invited,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Your membership in this organization is not active.');
});

test('authenticated user with active membership passes through', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('an own-tenant implicit-bound route resolves correctly', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk();
});

test('a foreign-tenant bound model still 404s once the middleware runs before binding', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertNotFound();
});
