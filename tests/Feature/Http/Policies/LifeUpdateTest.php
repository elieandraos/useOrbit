<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Tests\Support\PolicyPayload;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->life()->create();

    $this->patch(route('policies.life.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->life()->create(['organization_id' => $otherOrganization->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), PolicyPayload::life($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-life policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), PolicyPayload::life($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 rather than validation errors updating a non-life policy with an invalid payload', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.life.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), PolicyPayload::life($client, $carrier))
        ->assertRedirect(route('policies.life.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), PolicyPayload::life($client, $carrier, [
            'life' => ['sum_assured' => '200000.00'],
        ]));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $this->assertDatabaseHas('policy_life_details', [
        'policy_id' => $policy->id,
        'sum_assured' => '200000.00',
    ]);
});

test('a class field cannot be changed away from life', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::life($client, $carrier, ['class' => 'medical']);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('every canonical life subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::life($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Term', 'Whole life', 'Endowment', 'Group life']);

test('a subclass outside the life list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::life($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Standard', 'Universal life']);
