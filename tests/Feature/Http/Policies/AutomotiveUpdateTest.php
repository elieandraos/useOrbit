<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->automotive()->create();

    $this->patch(route('policies.automotive.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->automotive()->create(['organization_id' => $otherOrganization->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), PolicyPayload::automotive($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-automotive policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), PolicyPayload::automotive($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 rather than validation errors updating a non-automotive policy with an invalid payload', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.automotive.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), PolicyPayload::automotive($client, $carrier))
        ->assertRedirect(route('policies.automotive.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), PolicyPayload::automotive($client, $carrier, [
            'automotive' => ['plate_number' => '789 EF'],
        ]));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $this->assertDatabaseHas('policy_automotive_details', [
        'policy_id' => $policy->id,
        'plate_number' => '789 EF',
    ]);
});

test('a class field cannot be changed away from automotive', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, ['class' => 'medical']);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('a third party policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, [
        'subclass' => 'Third Party Liability',
        'automotive' => ['valuation_amount' => '10000.00', 'valuation_source' => 'Market value'],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('an all risk policy requires a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotiveAllRisk($client, $carrier);
    Arr::forget($payload, ['automotive.valuation_amount', 'automotive.valuation_source']);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('every canonical automotive subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = $subclass === 'All Risk'
        ? PolicyPayload::automotiveAllRisk($client, $carrier)
        : PolicyPayload::automotive($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Third Party Liability', 'All Risk', 'Compulsory']);

test('a subclass outside the automotive list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Third party liability', 'Dental']);

test('a compulsory policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::automotive($client, $carrier, [
        'subclass' => 'Compulsory',
        'automotive' => ['valuation_amount' => '10000.00', 'valuation_source' => 'Market value'],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});
