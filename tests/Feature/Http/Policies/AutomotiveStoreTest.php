<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;

function automotivePayload(Client $client, Carrier $carrier, string $subclass = 'Third Party Liability'): array
{
    $isAllRisk = $subclass === 'All Risk';

    return [
        'class' => 'automotive',
        'subclass' => $subclass,
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '800.00',
        'source' => 'client',
        'automotive' => [
            'plate_number' => '123 AB',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'valuation_amount' => $isAllRisk ? '35000.00' : null,
            'valuation_source' => $isAllRisk ? 'Carrier assessor' : null,
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $this->post(route('policies.automotive.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.automotive.store'))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when vehicle fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotivePayload($client, $carrier);
    unset($payload['automotive']);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.plate_number', 'automotive.make', 'automotive.model', 'automotive.year']);
});

test('store redirects to policies.automotive.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), automotivePayload($client, $carrier))
        ->assertRedirect(route('policies.automotive.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('a third party policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotivePayload($client, $carrier);
    $payload['automotive']['valuation_amount'] = '10000.00';
    $payload['automotive']['valuation_source'] = 'Market value';

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('an all risk policy requires a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotivePayload($client, $carrier, 'All Risk');
    unset($payload['automotive']['valuation_amount'], $payload['automotive']['valuation_source']);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('an all risk policy with a valuation is accepted', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), automotivePayload($client, $carrier, 'All Risk'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.automotive.show', Policy::query()->first()));
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), automotivePayload($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), automotivePayload($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});
