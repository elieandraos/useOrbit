<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $this->post(route('policies.expat.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.expat.store'))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when expat fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = Arr::except(PolicyPayload::expat($client, $carrier), ['expat']);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['expat.coverage_zone', 'expat.full_name', 'expat.gender', 'expat.nationality', 'expat.date_of_birth', 'expat.phone']);
});

test('store redirects to policies.expat.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), PolicyPayload::expat($client, $carrier))
        ->assertRedirect(route('policies.expat.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('an in-zone policy prohibits a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, [
        'expat' => ['coverage_zone' => 'in', 'travel_scope' => 'Worldwide'],
    ]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('an in-out zone policy requires a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expatInOut($client, $carrier);
    Arr::forget($payload, 'expat.travel_scope');

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('an in-out zone policy with a travel scope is accepted', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), PolicyPayload::expatInOut($client, $carrier))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.expat.show', Policy::query()->first()));
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), PolicyPayload::expat($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.expat.store'), PolicyPayload::expat($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});

test('every canonical expat subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasNoErrors();
})->with(['Worldwide', 'Schengen', 'GCC', 'Student']);

test('a subclass outside the expat list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['In', 'Pilgrim']);

test('the subclass and coverage zone are stored independently', function (string $subclass, string $coverageZone) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = $coverageZone === 'in_out'
        ? PolicyPayload::expatInOut($client, $carrier, ['subclass' => $subclass])
        : PolicyPayload::expat($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), $payload)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('policies', ['subclass' => $subclass]);
    $this->assertDatabaseHas('policy_expat_details', ['coverage_zone' => $coverageZone]);
})->with([
    ['Schengen', 'in'],
    ['Schengen', 'in_out'],
    ['Student', 'in'],
    ['Student', 'in_out'],
]);

test('an expat insured born today is accepted', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), PolicyPayload::expat($client, $carrier, ['expat' => ['date_of_birth' => today()->toDateString()]]))
        ->assertSessionHasNoErrors();
});

test('an expat insured born in the future is rejected', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.expat.store'), PolicyPayload::expat($client, $carrier, ['expat' => ['date_of_birth' => today()->addDay()->toDateString()]]))
        ->assertSessionHasErrors(['expat.date_of_birth']);
});
