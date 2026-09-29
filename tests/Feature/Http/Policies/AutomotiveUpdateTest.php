<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

function automotiveUpdatePayload(Client $client, Carrier $carrier, string $subclass = 'Third Party Liability'): array
{
    $isAllRisk = $subclass === 'All Risk';

    return [
        'class' => 'automotive',
        'subclass' => $subclass,
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '900.00',
        'source' => 'client',
        'automotive' => [
            'plate_number' => '789 EF',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'valuation_amount' => $isAllRisk ? '40000.00' : null,
            'valuation_source' => $isAllRisk ? 'Market value' : null,
        ],
    ];
}

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
        ->patch(route('policies.automotive.update', $policy), automotiveUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-automotive policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), automotiveUpdatePayload($client, $carrier))
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
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.automotive.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), automotiveUpdatePayload($client, $carrier))
        ->assertRedirect(route('policies.automotive.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), automotiveUpdatePayload($client, $carrier));

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

    $payload = automotiveUpdatePayload($client, $carrier);
    $payload['class'] = 'medical';

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('a third party policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotiveUpdatePayload($client, $carrier);
    $payload['automotive']['valuation_amount'] = '10000.00';
    $payload['automotive']['valuation_source'] = 'Market value';

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('an all risk policy requires a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotiveUpdatePayload($client, $carrier, 'All Risk');
    unset($payload['automotive']['valuation_amount'], $payload['automotive']['valuation_source']);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});

test('every canonical automotive subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotiveUpdatePayload($client, $carrier, $subclass);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Third Party Liability', 'All Risk', 'Compulsory']);

test('a subclass outside the automotive list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotiveUpdatePayload($client, $carrier, $subclass);

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Third party liability', 'Dental']);

test('a compulsory policy prohibits a vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = automotiveUpdatePayload($client, $carrier, 'Compulsory');
    $payload['automotive']['valuation_amount'] = '10000.00';
    $payload['automotive']['valuation_source'] = 'Market value';

    $this->actingAs($user)
        ->patch(route('policies.automotive.update', $policy), $payload)
        ->assertSessionHasErrors(['automotive.valuation_amount', 'automotive.valuation_source']);
});
