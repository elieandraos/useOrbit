<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('the edit page exposes the policy parties it pre-fills', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Edit')
            ->where('policy.client.id', $client->id)
            ->where('policy.carrier.id', $carrier->id)
            ->where('policy.agent.id', $agent->id)
        );
});

test('the edit page exposes a null agent for a policy with no assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('authenticated user gets 404 editing a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->expat()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 editing a non-expat policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertNotFound();
});

test('the edit page offers the canonical subclasses with the stored subclass selected', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'subclass' => 'GCC',
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('subclasses', ['Worldwide', 'Schengen', 'GCC', 'Student'])
            ->where('policy.subclass', 'GCC')
        );
});

test('the edit page receives the policy and the shared and expat form options', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Edit')
            ->hasAll(['policy', 'clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'currencies', 'subclasses', 'coverageZones', 'genders', 'countries'])
        );
});

test('the edit page offers active parties and the policy\'s own archived parties, but no other archived ones', function () {
    $user = User::factory()->withOrganization()->create();
    $activeClient = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Adam']);
    $activeCarrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Allied Insurance']);
    $activeAgent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Adam']);
    $archivedClient = Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id, 'first_name' => 'Zoe']);
    $archivedCarrier = Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id, 'name' => 'Zenith Insurance']);
    $archivedAgent = Agent::factory()->forOrganization($user)->archived()->create(['first_name' => 'Zoe']);
    Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    Agent::factory()->forOrganization($user)->archived()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'client_id' => $archivedClient->id,
        'carrier_id' => $archivedCarrier->id,
        'agent_id' => $archivedAgent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients', 2)
            ->where('clients.0.id', $activeClient->id)
            ->where('clients.1.id', $archivedClient->id)
            ->has('carriers', 2)
            ->where('carriers.0.id', $activeCarrier->id)
            ->where('carriers.1.id', $archivedCarrier->id)
            ->has('agents', 2)
            ->where('agents.0.id', $activeAgent->id)
            ->where('agents.1.id', $archivedAgent->id)
        );
});

test('the edit page shows the stored currency rather than the organization default', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->lbp()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.currency_id', $policy->currency_id)
            ->missing('defaultCurrencyId')
        );
});
