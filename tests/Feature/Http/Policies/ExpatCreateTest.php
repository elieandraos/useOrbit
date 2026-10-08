<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('the create page offers the canonical expat subclasses', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier)))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Create')
            ->where('subclasses', ['Worldwide', 'Schengen', 'GCC', 'Student'])
        );
});

test('the create page receives the shared and expat form options', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier)))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'sources', 'currencies', 'subclasses', 'coverageZones', 'genders', 'countries'])
        );
});

test('the create page offers every country and no default country', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCountry = Country::factory()->create();

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier)))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('countries', Country::query()->count())
            ->where('countries', fn ($countries) => collect($countries)->pluck('id')->contains($otherCountry->id))
            ->missing('defaultCountryId')
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
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier)))
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
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier)))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('defaultCurrencyId', $organization->default_currency_id)
            ->where('currencies.0.id', $organization->default_currency_id)
        );
});

test('the create page pre-selects no currency when the organization has no default', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier)))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('defaultCurrencyId', null));
});

test('the create page summarizes the first-step choices with their labels', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier, [
            'class' => 'fire',
            'type' => 'group',
            'agent_id' => $agent->id,
            'source' => 'friend',
        ])))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyExpat/Create')
            ->where('entry', [
                'class' => ['value' => 'expat', 'label' => 'Expat'],
                'type' => ['value' => 'group', 'label' => 'Group'],
                'client' => ['id' => $client->id, 'full_name' => $client->full_name],
                'carrier' => ['id' => $carrier->id, 'name' => $carrier->name],
                'agent' => ['id' => $agent->id, 'full_name' => $agent->full_name],
                'source' => ['value' => 'friend', 'label' => 'Friend'],
            ])
        );
});

test('the create page leaves out an agent that is archived or from another organization', function (string $case) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = match ($case) {
        'archived' => Agent::factory()->forOrganization($user)->archived()->create(),
        'other organization' => Agent::factory()->create(),
    };

    $this->actingAs($user)
        ->get(route('policies.expat.create', PolicyPayload::entryQuery($client, $carrier, ['agent_id' => $agent->id])))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('entry.agent', null));
})->with(['archived', 'other organization']);

test('the create page sends the user back to the first step, keeping the valid choices, when a required one is missing or no longer valid', function (string $case, string $invalidKey) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $query = PolicyPayload::entryQuery($client, $carrier);

    $query[$invalidKey] = match ($case) {
        'missing client' => null,
        'archived client' => Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id])->id,
        'other-organization client' => Client::factory()->create()->id,
        'archived carrier' => Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id])->id,
        'unknown type' => 'family',
        'array source' => ['client'],
    };

    $this->actingAs($user)
        ->get(route('policies.expat.create', array_filter($query, fn (mixed $value): bool => $value !== null)))
        ->assertRedirect(route('policies.create', ['class' => 'expat', ...Arr::except(PolicyPayload::entryQuery($client, $carrier), $invalidKey)]))
        ->assertHasInertiaFlash('warning', 'Some of your choices are missing or no longer available.');
})->with([
    'missing client' => ['missing client', 'client_id'],
    'archived client' => ['archived client', 'client_id'],
    'other-organization client' => ['other-organization client', 'client_id'],
    'archived carrier' => ['archived carrier', 'carrier_id'],
    'unknown type' => ['unknown type', 'type'],
    'array source' => ['array source', 'source'],
]);
