<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->expat()->create();

    $this->patch(route('policies.expat.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->expat()->create(['organization_id' => $otherOrganization->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-expat policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 rather than validation errors updating a non-expat policy with an invalid payload', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.expat.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier))
        ->assertRedirect(route('policies.expat.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier, [
            'expat' => ['full_name' => 'Rami Haddad'],
        ]));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $this->assertDatabaseHas('policy_expat_details', [
        'policy_id' => $policy->id,
        'full_name' => 'Rami Haddad',
    ]);
});

test('a class field cannot be changed away from expat', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, ['class' => 'medical']);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('an in-zone policy prohibits a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, [
        'expat' => ['coverage_zone' => 'in', 'travel_scope' => 'Worldwide'],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('an in-out zone policy requires a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expatInOut($client, $carrier);
    Arr::forget($payload, 'expat.travel_scope');

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('every canonical expat subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Worldwide', 'Schengen', 'GCC', 'Student']);

test('a subclass outside the expat list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::expat($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['In', 'Pilgrim']);

test('an expat insured born in the future is rejected on update', function () {
    $this->freezeTime();
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier, ['expat' => ['date_of_birth' => today()->addDay()->toDateString()]]))
        ->assertSessionHasErrors(['expat.date_of_birth']);
});

test('any existing expat country is accepted on update', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $country = Country::factory()->create();

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier, ['expat' => ['country_id' => $country->id]]))
        ->assertSessionHasNoErrors();
});

test('an expat country that does not exist is rejected on update', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier, ['expat' => ['country_id' => 999999]]))
        ->assertSessionHasErrors(['expat.country_id']);
});

test('update rejects a missing or non-existent currency', function (?int $currencyId) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier, ['currency_id' => $currencyId]))
        ->assertSessionHasErrors(['currency_id']);

    expect($policy->fresh()->currency_id)->toBe($policy->currency_id);
})->with(['missing' => [null], 'non-existent' => [999999]]);

test('update keeps the stored status whether or not a status is posted', function (PolicyStatus $status, array $posted) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id, 'status' => $status]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), PolicyPayload::expat($client, $carrier, $posted))
        ->assertSessionHasNoErrors();

    expect($policy->fresh()->status)->toBe($status);
})->with([
    'cancelled' => PolicyStatus::Cancelled,
    'frozen' => PolicyStatus::Frozen,
])->with([
    'without a posted status' => [[]],
    'with a posted status' => [['status' => 'active']],
]);
