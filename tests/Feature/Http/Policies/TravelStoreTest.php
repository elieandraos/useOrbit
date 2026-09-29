<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

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

    $payload = Arr::except(PolicyPayload::travel($client, $carrier), ['travel']);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), $payload)
        ->assertSessionHasErrors(['travel.destination', 'travel.trip_start_date', 'travel.trip_end_date', 'travel.travelers', 'travel.coverage_tier']);
});

test('store returns a validation error when the trip end date precedes the start date', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::travel($client, $carrier, [
        'travel' => ['trip_start_date' => '2026-06-01', 'trip_end_date' => '2026-05-01'],
    ]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), $payload)
        ->assertSessionHasErrors(['travel.trip_end_date']);
});

test('store redirects to policies.travel.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), PolicyPayload::travel($client, $carrier))
        ->assertRedirect(route('policies.travel.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), PolicyPayload::travel($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.travel.store'), PolicyPayload::travel($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});

test('every canonical travel subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::travel($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), $payload)
        ->assertSessionHasNoErrors();
})->with(['Schengen', 'Worldwide', 'Student', 'Pilgrim']);

test('a subclass outside the travel list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::travel($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.travel.store'), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Premium', 'GCC']);
