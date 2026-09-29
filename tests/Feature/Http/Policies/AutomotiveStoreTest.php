<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

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

    $payload = Arr::except(PolicyPayload::automotive($client, $carrier), ['automotive']);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.plate_number', 'automotive.make', 'automotive.model', 'automotive.year']);
});

test('store redirects to policies.automotive.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), PolicyPayload::automotive($client, $carrier))
        ->assertRedirect(route('policies.automotive.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('a third party policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, [
        'subclass' => 'Third Party Liability',
        'automotive' => ['valuation_amount' => '10000.00', 'valuation_source' => 'Market value'],
    ]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('an all risk policy requires a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotiveAllRisk($client, $carrier);
    Arr::forget($payload, ['automotive.valuation_amount', 'automotive.valuation_source']);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('an all risk policy with a valuation is accepted', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), PolicyPayload::automotiveAllRisk($client, $carrier))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.automotive.show', Policy::query()->first()));
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), PolicyPayload::automotive($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), PolicyPayload::automotive($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});

test('every canonical automotive subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = $subclass === 'All Risk'
        ? PolicyPayload::automotiveAllRisk($client, $carrier)
        : PolicyPayload::automotive($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasNoErrors();
})->with(['Third Party Liability', 'All Risk', 'Compulsory']);

test('a subclass outside the automotive list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Third party liability', 'Dental']);

test('a compulsory policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, [
        'subclass' => 'Compulsory',
        'automotive' => ['valuation_amount' => '10000.00', 'valuation_source' => 'Market value'],
    ]);

    $this->actingAs($user)
        ->post(route('policies.automotive.store'), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});
