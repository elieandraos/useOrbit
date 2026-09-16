<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;

function fireUpdateLocationIds(): array
{
    $country = Country::query()->firstOrCreate(
        ['iso2' => 'LB'],
        ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
    );

    $state = State::query()->firstOrCreate(['country_id' => $country->id, 'name' => 'Beirut']);

    return [$state->id, $country->id];
}

function fireUpdatePayload(Client $client, Carrier $carrier, int $stateId, int $countryId): array
{
    return [
        'class' => 'fire',
        'subclass' => 'Standard',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '700.00',
        'source' => 'client',
        'fire' => [
            'property_type' => 'Warehouse',
            'floor_area' => 400,
            'year_built' => 1998,
            'street' => 'Sin El Fil Road',
            'building_floor' => null,
            'city' => 'Beirut',
            'state_id' => $stateId,
            'country_id' => $countryId,
            'sum_insured' => '500000.00',
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->fire()->create();

    $this->patch(route('policies.fire.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->fire()->create(['organization_id' => $otherOrganization->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireUpdateLocationIds();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), fireUpdatePayload($client, $carrier, $stateId, $countryId))
        ->assertNotFound();
});

test('a user gets 404 updating a non-fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireUpdateLocationIds();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), fireUpdatePayload($client, $carrier, $stateId, $countryId))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.fire.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireUpdateLocationIds();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), fireUpdatePayload($client, $carrier, $stateId, $countryId))
        ->assertRedirect(route('policies.fire.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireUpdateLocationIds();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), fireUpdatePayload($client, $carrier, $stateId, $countryId));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $this->assertDatabaseHas('policy_fire_details', [
        'policy_id' => $policy->id,
        'street' => 'Sin El Fil Road',
    ]);
});

test('a class field cannot be changed away from fire', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    [$stateId, $countryId] = fireUpdateLocationIds();

    $payload = fireUpdatePayload($client, $carrier, $stateId, $countryId);
    $payload['class'] = 'medical';

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});
