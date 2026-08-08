<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('authenticated user with suspended membership is redirected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Member)->create([
        'status' => OrganizationMemberStatus::Suspended,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Your membership in this organization is not active.');
});

test('authenticated user with invited membership is redirected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization, OrganizationRole::Member)->create([
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
