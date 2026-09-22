<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('policies.create'))
        ->assertRedirect(route('login'));
});

test('the endpoint returns no selected client by default', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selectedClientId', null));
});

test('a client_id query param pre-selects that client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.create', ['client_id' => $client->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selectedClientId', $client->id));
});

test('a client_id from another organization is ignored', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $otherClient = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('policies.create', ['client_id' => $otherClient->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selectedClientId', null));
});
