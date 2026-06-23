<?php

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;

test('authenticated user with no current_organization_id is redirected', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});

test('authenticated user with suspended membership is redirected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['current_organization_id' => $organization->id]);
    $user->organizations()->attach($organization, ['role' => 'member', 'status' => OrganizationMemberStatus::Suspended->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});

test('authenticated user with invited membership is redirected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['current_organization_id' => $organization->id]);
    $user->organizations()->attach($organization, ['role' => 'member', 'status' => OrganizationMemberStatus::Invited->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});

test('authenticated user with active membership passes through', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['current_organization_id' => $organization->id]);
    $user->organizations()->attach($organization, ['role' => 'member', 'status' => OrganizationMemberStatus::Active->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
