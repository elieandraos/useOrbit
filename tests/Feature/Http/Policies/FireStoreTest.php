<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;

function fireLocationIds(): array
{
    $country = Country::query()->firstOrCreate(
        ['iso2' => 'LB'],
        ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
    );

    $state = State::query()->firstOrCreate(['country_id' => $country->id, 'name' => 'Beirut']);

    return [$state->id, $country->id];
}

function firePayload(Client $client, Carrier $carrier, int $stateId, int $countryId): array
{
    return [
        'class' => 'fire',
        'subclass' => 'Standard',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '600.00',
        'source' => 'client',
        'fire' => [
            'property_type' => 'Residential apartment',
            'floor_area' => 180,
            'year_built' => 2005,
            'street' => 'Hamra Street',
            'building_floor' => 'Floor 3',
            'city' => 'Beirut',
            'state_id' => $stateId,
            'country_id' => $countryId,
            'sum_insured' => '250000.00',
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $this->post(route('policies.fire.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when property fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $payload = firePayload($client, $carrier, $stateId, $countryId);
    unset($payload['fire']);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasErrors(['fire.property_type', 'fire.floor_area', 'fire.street', 'fire.city', 'fire.state_id', 'fire.country_id', 'fire.sum_insured']);
});

test('store redirects to policies.fire.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), firePayload($client, $carrier, $stateId, $countryId))
        ->assertRedirect(route('policies.fire.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('year built is optional', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $payload = firePayload($client, $carrier, $stateId, $countryId);
    unset($payload['fire']['year_built']);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.fire.show', Policy::query()->first()));
});

test('building floor is optional', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $payload = firePayload($client, $carrier, $stateId, $countryId);
    unset($payload['fire']['building_floor']);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasNoErrors();
});

test('a floor area exceeding the column range is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $payload = firePayload($client, $carrier, $stateId, $countryId);
    $payload['fire']['floor_area'] = 70000;

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasErrors(['fire.floor_area']);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), firePayload($otherClient, $carrier, $stateId, $countryId))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();
    [$stateId, $countryId] = fireLocationIds();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), firePayload($client, $otherCarrier, $stateId, $countryId))
        ->assertSessionHasErrors(['carrier_id']);
});

test('no insureds are created for a fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireLocationIds();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), firePayload($client, $carrier, $stateId, $countryId));

    $this->assertDatabaseCount('policy_insureds', 0);
});
