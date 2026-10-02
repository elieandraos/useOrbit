<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;
use Tests\Support\PolicyPayload;

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
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state))
        ->assertNotFound();
});

test('a user gets 404 updating a non-fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state))
        ->assertNotFound();
});

test('a user gets 404 rather than validation errors updating a non-fire policy with an invalid payload', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.fire.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state))
        ->assertRedirect(route('policies.fire.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state, [
            'fire' => ['street' => 'Sin El Fil Road'],
        ]));

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
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state, ['class' => 'medical']);

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('every canonical fire subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Building', 'Contents', 'Business interruption', 'All risk']);

test('a subclass outside the fire list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $payload = PolicyPayload::fire($client, $carrier, $state, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Standard', 'All Risk']);

test('a fire country that does not exist is rejected on update', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state, ['fire' => ['country_id' => 999999]]))
        ->assertSessionHasErrors(['fire.country_id']);
});

test('update rejects a missing or non-existent currency', function (?int $currencyId) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state, ['currency_id' => $currencyId]))
        ->assertSessionHasErrors(['currency_id']);

    expect($policy->fresh()->currency_id)->toBe($policy->currency_id);
})->with(['missing' => [null], 'non-existent' => [999999]]);

test('update accepts the largest sum insured a policy holds', function () {
    $amount = '9999999999999.99';
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state, ['fire' => ['sum_insured' => $amount]]))
        ->assertSessionHasNoErrors();
});

test('update rejects a sum insured above the largest amount or with more than two decimals', function (string $amount) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state, ['fire' => ['sum_insured' => $amount]]))
        ->assertSessionHasErrors(['fire.sum_insured']);
})->with(['above the maximum' => ['10000000000000.00'], 'three decimals' => ['1000.001']]);

test('changing the currency stores every amount exactly as submitted, without conversion', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id, 'premium_amount' => '600.00']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->lebanon()->create();
    $lbp = Currency::factory()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state, [
            'currency_id' => $lbp->id,
            'premium_amount' => '600.00',
            'discount_amount' => '50.00',
            'fire' => ['sum_insured' => '250000.00'],
        ]))
        ->assertSessionHasNoErrors();

    $policy->refresh();

    expect($policy->currency_id)->toBe($lbp->id)
        ->and($policy->premium_amount)->toBe('600.00')
        ->and($policy->discount_amount)->toBe('50.00')
        ->and($policy->fireDetails->sum_insured)->toBe('250000.00');
});
