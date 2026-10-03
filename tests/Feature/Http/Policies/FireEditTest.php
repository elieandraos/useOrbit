<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;

test('the edit page exposes the policy parties it pre-fills', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Edit')
            ->where('policy.client.id', $client->id)
            ->where('policy.carrier.id', $carrier->id)
            ->where('policy.agent.id', $agent->id)
        );
});

test('the edit page exposes a null agent for a policy with no assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('authenticated user gets 404 editing a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->fire()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 editing a non-fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertNotFound();
});

test('the edit page offers the canonical subclasses with the stored subclass selected', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'subclass' => 'Contents',
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('subclasses', ['Building', 'Contents', 'Business interruption', 'All risk'])
            ->where('policy.subclass', 'Contents')
        );
});

test('the edit page receives the policy and the shared and fire form options', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Edit')
            ->hasAll(['policy', 'clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'currencies', 'subclasses', 'countries'])
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
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'client_id' => $archivedClient->id,
        'carrier_id' => $archivedCarrier->id,
        'agent_id' => $archivedAgent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients', 2)
            ->has('clients.0', fn ($option) => $option->where('id', $activeClient->id)->where('full_name', $activeClient->full_name))
            ->where('clients.1.id', $archivedClient->id)
            ->has('carriers', 2)
            ->has('carriers.0', fn ($option) => $option->where('id', $activeCarrier->id)->where('name', $activeCarrier->name)->has('branches', 0))
            ->where('carriers.1.id', $archivedCarrier->id)
            ->has('agents', 2)
            ->has('agents.0', fn ($option) => $option->where('id', $activeAgent->id)->where('full_name', $activeAgent->full_name))
            ->where('agents.1.id', $archivedAgent->id)
        );
});

test('the edit page shows the stored currency rather than the organization default', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->lbp()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.currency_id', $policy->currency_id)
            ->missing('defaultCurrencyId')
        );
});

test('the edit page offers the stored country of any market and never applies the organization default', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $state = State::factory()->create();
    $policy->fireDetails->update(['country_id' => $state->country_id, 'state_id' => $state->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.edit', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.country_id', $state->country_id)
            ->where('countries', fn ($countries) => collect($countries)->pluck('id')->contains($state->country_id))
            ->missing('defaultCountryId')
        );
});
