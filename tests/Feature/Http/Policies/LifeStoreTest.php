<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $this->post(route('policies.life.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.life.store'))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store returns validation errors when life fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = Arr::except(PolicyPayload::life($client, $carrier), ['life']);

    $this->actingAs($user)
        ->post(route('policies.life.store'), $payload)
        ->assertSessionHasErrors(['life.sum_assured', 'life.term_years', 'life.beneficiaries']);
});

test('store redirects to policies.life.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($client, $carrier))
        ->assertRedirect(route('policies.life.show', Policy::query()->first()))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($otherClient, $carrier))
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($client, $otherCarrier))
        ->assertSessionHasErrors(['carrier_id']);
});

test('every canonical life subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::life($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), $payload)
        ->assertSessionHasNoErrors();
})->with(['Term', 'Whole life', 'Endowment', 'Group life']);

test('a subclass outside the life list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::life($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Standard', 'Universal life']);

test('store saves the selected currency', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $currency = Currency::factory()->create();

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($client, $carrier, ['currency_id' => $currency->id]))
        ->assertSessionHasNoErrors();

    expect(Policy::query()->sole()->currency_id)->toBe($currency->id);
});

test('store rejects a missing or non-existent currency', function (?int $currencyId) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($client, $carrier, ['currency_id' => $currencyId]))
        ->assertSessionHasErrors(['currency_id']);

    expect(Policy::query()->exists())->toBeFalse();
})->with(['missing' => [null], 'non-existent' => [999999]]);

test('store accepts the largest sum assured a policy holds', function () {
    $amount = '9999999999999.99';
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($client, $carrier, ['life' => ['sum_assured' => $amount]]))
        ->assertSessionHasNoErrors();
});

test('store rejects a sum assured above the largest amount or with more than two decimals', function (string $amount) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.life.store'), PolicyPayload::life($client, $carrier, ['life' => ['sum_assured' => $amount]]))
        ->assertSessionHasErrors(['life.sum_assured']);
})->with(['above the maximum' => ['10000000000000.00'], 'three decimals' => ['1000.001']]);
