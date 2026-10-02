<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('the create page offers the canonical automotive subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.automotive.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyAutomotive/Create')
            ->where('subclasses', ['Third Party Liability', 'All Risk', 'Compulsory'])
        );
});

test('the create page receives the shared and automotive form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.automotive.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyAutomotive/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'currencies', 'subclasses'])
        );
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
        ->get(route('policies.automotive.create'))
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

test('the create page pre-selects the organization default currency', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('policies.automotive.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('defaultCurrencyId', $organization->default_currency_id)
            ->where('currencies.0.id', $organization->default_currency_id)
        );
});

test('the create page pre-selects no currency when the organization has no default', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.automotive.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('defaultCurrencyId', null));
});
