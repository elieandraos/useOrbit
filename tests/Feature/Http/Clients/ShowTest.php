<?php

declare(strict_types=1);

use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.show', $client))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a client from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->create(['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertHasResource('client', ClientResource::make($client->load('country')));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertNotFound();
});
