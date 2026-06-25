<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('clients.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can list their organization clients', function () {
    $user = User::factory()->withOrganization()->create();
    Client::factory(2)->create(['organization_id' => $user->current_organization_id]);

    $this->assertDatabaseCount('clients', 2);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->has('clients.data', 2)
        );
});

test('clients from another organization are not included', function () {
    $user = User::factory()->withOrganization()->create();
    Client::factory(2)->create(['organization_id' => $user->current_organization_id]);

    $otherOrganization = Organization::factory()->create();
    Client::factory(3)->create(['organization_id' => $otherOrganization->id]);

    $this->assertDatabaseCount('clients', 5);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 2)
        );
});
