<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
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

test('the create page receives the shared form options with the policy classes', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Policies/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'classes', 'selectedClientId'])
        );
});

test('the create page orders clients and agents by id and carriers by name', function () {
    $user = User::factory()->withOrganization()->create();
    $firstClient = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $secondClient = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Zenith Insurance']);
    Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Allied Insurance']);
    $firstAgent = Agent::factory()->forOrganization($user)->create();
    $secondAgent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.0.id', $firstClient->id)
            ->where('clients.1.id', $secondClient->id)
            ->where('carriers.0.name', 'Allied Insurance')
            ->where('carriers.1.name', 'Zenith Insurance')
            ->where('agents.0.id', $firstAgent->id)
            ->where('agents.1.id', $secondAgent->id)
        );
});
