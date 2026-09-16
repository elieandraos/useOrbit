<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

function travelUpdatePayload(Client $client, Carrier $carrier): array
{
    return [
        'class' => 'travel',
        'subclass' => 'Premium',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '220.00',
        'source' => 'client',
        'travel' => [
            'destination' => 'Spain',
            'trip_start_date' => '2026-07-01',
            'trip_end_date' => '2026-07-10',
            'travelers' => 'Amir Haddad',
            'coverage_tier' => 'Premium',
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->travel()->create();

    $this->patch(route('policies.travel.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->travel()->create(['organization_id' => $otherOrganization->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), travelUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-travel policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), travelUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.travel.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), travelUpdatePayload($client, $carrier))
        ->assertRedirect(route('policies.travel.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), travelUpdatePayload($client, $carrier));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
    $this->assertDatabaseHas('policy_travel_details', [
        'policy_id' => $policy->id,
        'destination' => 'Spain',
    ]);
});

test('a class field cannot be changed away from travel', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = travelUpdatePayload($client, $carrier);
    $payload['class'] = 'medical';

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});
