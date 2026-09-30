<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
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

test('a fire country outside the configured markets is rejected on update', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    $this->actingAs($user)
        ->patch(route('policies.fire.update', $policy), PolicyPayload::fire($client, $carrier, $state))
        ->assertSessionHasErrors(['fire.country_id']);
});
