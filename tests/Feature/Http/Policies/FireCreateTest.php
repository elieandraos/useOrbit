<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\User;

test('the create page offers the canonical fire subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Create')
            ->where('subclasses', ['Building', 'Contents', 'Business interruption', 'All risk'])
        );
});

test('the create page receives the shared and fire form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'subclasses', 'countries'])
        );
});

test('the create page offers only the configured market countries', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->firstOrCreate(['iso2' => 'LB'], ['name' => 'Lebanon', 'iso3' => 'LBN']);
    Country::factory()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('countries', 1)
            ->where('countries.0.id', $lebanon->id)
        );
});

test('no country is offered when no market is configured', function () {
    config(['markets.countries' => []]);
    $user = User::factory()->withOrganization()->create();
    Country::query()->firstOrCreate(['iso2' => 'LB'], ['name' => 'Lebanon', 'iso3' => 'LBN']);

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('countries', 0));
});

test('the create page offers only active clients, carriers and agents', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->create();
    Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->where('clients.0.id', $client->id)
            ->has('carriers', 1)
            ->where('carriers.0.id', $carrier->id)
            ->has('agents', 1)
            ->where('agents.0.id', $agent->id)
        );
});
