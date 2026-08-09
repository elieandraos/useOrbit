<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
