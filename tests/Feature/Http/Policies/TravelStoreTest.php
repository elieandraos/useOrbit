<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;

function travelPayload(Client $client, Carrier $carrier): array
{
    return [
        'class' => 'travel',
        'subclass' => 'Standard',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '150.00',
        'source' => 'client',
        'travel' => [
            'destination' => 'Portugal',
            'trip_start_date' => '2026-06-01',
            'trip_end_date' => '2026-06-15',
            'travelers' => 'Jane Doe, John Doe',
            'coverage_tier' => 'Standard',
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $this->post(route('policies.travel.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.travel.store'))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when trip fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = travelPayload($client, $carrier);
    unset($payload['travel']);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), $payload)
        ->assertSessionHasErrors(['travel.destination', 'travel.trip_start_date', 'travel.trip_end_date', 'travel.travelers', 'travel.coverage_tier']);
});

test('store returns a validation error when the trip end date precedes the start date', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = travelPayload($client, $carrier);
    $payload['travel']['trip_end_date'] = '2026-05-01';

    $this->actingAs($user)
        ->post(route('policies.travel.store'), $payload)
        ->assertSessionHasErrors(['travel.trip_end_date']);
});

test('store redirects to policies.travel.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), travelPayload($client, $carrier))
        ->assertRedirect(route('policies.travel.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), travelPayload($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.travel.store'), travelPayload($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});
