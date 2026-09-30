<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $this->post(route('policies.fire.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when property fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = Arr::except(PolicyPayload::fire($client, $carrier, $state), ['fire']);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasErrors(['fire.property_type', 'fire.floor_area', 'fire.street', 'fire.city', 'fire.state_id', 'fire.country_id', 'fire.sum_insured']);
});

test('a missing governorate is reported by its form label', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state);
    Arr::forget($payload, 'fire.state_id');

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasErrors(['fire.state_id' => 'The governorate field is required.']);
});

test('store redirects to policies.fire.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), PolicyPayload::fire($client, $carrier, $state))
        ->assertRedirect(route('policies.fire.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('year built is optional', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state);
    Arr::forget($payload, 'fire.year_built');

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.fire.show', Policy::query()->first()));
});

test('building floor is optional', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state);
    Arr::forget($payload, 'fire.building_floor');

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasNoErrors();
});

test('a floor area exceeding the column range is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state, ['fire' => ['floor_area' => 70000]]);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasErrors(['fire.floor_area']);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), PolicyPayload::fire($otherClient, $carrier, $state))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), PolicyPayload::fire($client, $otherCarrier, $state))
        ->assertSessionHasErrors(['carrier_id']);
});

test('no insureds are created for a fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), PolicyPayload::fire($client, $carrier, $state));

    $this->assertDatabaseCount('policy_insureds', 0);
});

test('every canonical fire subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasNoErrors();
})->with(['Building', 'Contents', 'Business interruption', 'All risk']);

test('a subclass outside the fire list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.fire.store'), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Standard', 'All Risk']);

test('a fire country outside the configured markets is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), PolicyPayload::fire($client, $carrier, $state))
        ->assertSessionHasErrors(['fire.country_id']);
});

test('no fire country is accepted when no market is configured', function () {
    config(['markets.countries' => []]);
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->post(route('policies.fire.store'), PolicyPayload::fire($client, $carrier, $state))
        ->assertSessionHasErrors(['fire.country_id']);
});
