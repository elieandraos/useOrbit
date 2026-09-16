<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

function lifeUpdatePayload(Client $client, Carrier $carrier): array
{
    return [
        'class' => 'life',
        'subclass' => 'Term',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '700.00',
        'source' => 'client',
        'life' => [
            'sum_assured' => '200000.00',
            'term_years' => 15,
            'smoker' => true,
            'beneficiaries' => 'John Smith (100%)',
        ],
    ];
}

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
        ->patch(route('policies.life.update', $policy), lifeUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-life policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), lifeUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.life.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), lifeUpdatePayload($client, $carrier))
        ->assertRedirect(route('policies.life.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), lifeUpdatePayload($client, $carrier));

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

    $payload = lifeUpdatePayload($client, $carrier);
    $payload['class'] = 'medical';

    $this->actingAs($user)
        ->patch(route('policies.life.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});
