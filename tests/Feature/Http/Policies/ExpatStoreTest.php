<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;

function expatPayload(Client $client, Carrier $carrier, string $coverageZone = 'in'): array
{
    $isInOut = $coverageZone === 'in_out';

    return [
        'class' => 'expat',
        'subclass' => $isInOut ? 'In-Out' : 'In',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1200.00',
        'source' => 'client',
        'expat' => [
            'coverage_zone' => $coverageZone,
            'travel_scope' => $isInOut ? 'Worldwide' : null,
            'full_name' => 'Karim Saad',
            'gender' => 'male',
            'nationality' => 'Lebanese',
            'date_of_birth' => '1985-04-12',
            'phone' => '+96170123456',
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $this->post(route('policies.expat.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.expat.store'))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when expat fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatPayload($client, $carrier);
    unset($payload['expat']);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['expat.coverage_zone', 'expat.full_name', 'expat.gender', 'expat.nationality', 'expat.date_of_birth', 'expat.phone']);
});

test('store redirects to policies.expat.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), expatPayload($client, $carrier))
        ->assertRedirect(route('policies.expat.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('an in-zone policy prohibits a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatPayload($client, $carrier);
    $payload['expat']['travel_scope'] = 'Worldwide';

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('an in-out zone policy requires a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatPayload($client, $carrier, 'in_out');
    unset($payload['expat']['travel_scope']);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('an in-out zone policy with a travel scope is accepted', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), expatPayload($client, $carrier, 'in_out'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.expat.show', Policy::query()->first()));
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), expatPayload($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.expat.store'), expatPayload($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});
