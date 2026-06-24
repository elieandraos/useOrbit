<?php

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['current_organization_id' => $organization->id]);
    $user->organizations()->attach($organization, ['role' => 'member', 'status' => OrganizationMemberStatus::Active->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
