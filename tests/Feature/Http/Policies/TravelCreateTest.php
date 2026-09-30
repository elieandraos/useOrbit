<?php

declare(strict_types=1);

use App\Enums\TravelCoverageTier;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\User;

test('the create page offers the canonical travel subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.travel.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyTravel/Create')
            ->where('subclasses', ['Schengen', 'Worldwide', 'Student', 'Pilgrim'])
        );
});

test('the create page receives the shared and travel form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.travel.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyTravel/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'subclasses', 'coverageTiers'])
        );
});

test('the create page offers the travel coverage tiers', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.travel.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('coverageTiers', TravelCoverageTier::all()));
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
        ->get(route('policies.travel.create'))
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
