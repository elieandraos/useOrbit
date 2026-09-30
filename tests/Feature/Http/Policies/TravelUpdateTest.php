<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Tests\Support\PolicyPayload;

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
        ->patch(route('policies.travel.update', $policy), PolicyPayload::travel($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-travel policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), PolicyPayload::travel($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 rather than validation errors updating a non-travel policy with an invalid payload', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy))
        ->assertSessionHasErrors(['policy_number', 'class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.travel.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), PolicyPayload::travel($client, $carrier))
        ->assertRedirect(route('policies.travel.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), PolicyPayload::travel($client, $carrier, [
            'travel' => ['destination' => 'Spain'],
        ]));

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

    $payload = PolicyPayload::travel($client, $carrier, ['class' => 'medical']);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('every canonical travel subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::travel($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Schengen', 'Worldwide', 'Student', 'Pilgrim']);

test('a subclass outside the travel list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = PolicyPayload::travel($client, $carrier, ['subclass' => $subclass]);

    $this->actingAs($user)
        ->patch(route('policies.travel.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['Premium', 'GCC']);
