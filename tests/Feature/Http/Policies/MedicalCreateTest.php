<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('the create page offers the canonical medical subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyMedical/Create')
            ->where('subclasses', ['Hospitalization', 'Outpatient', 'Dental', 'Vision', 'Major medical'])
        );
});

test('the create page receives the shared and medical form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyMedical/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'sources', 'currencies', 'subclasses', 'coverageScopes', 'classTiers', 'genders'])
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
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->has('clients.0', fn ($option) => $option->where('id', $client->id)->where('full_name', $client->full_name))
            ->has('carriers', 1)
            ->has('carriers.0', fn ($option) => $option->where('id', $carrier->id)->where('name', $carrier->name)->has('branches', 0))
            ->has('agents', 1)
            ->has('agents.0', fn ($option) => $option->where('id', $agent->id)->where('full_name', $agent->full_name))
        );
});

test('the create page pre-selects the organization default currency', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('defaultCurrencyId', $organization->default_currency_id)
            ->where('currencies.0.id', $organization->default_currency_id)
        );
});

test('the create page pre-selects no currency when the organization has no default', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.medical.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('defaultCurrencyId', null));
});
