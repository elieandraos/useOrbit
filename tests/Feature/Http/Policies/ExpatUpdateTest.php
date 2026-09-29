<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

function expatUpdatePayload(Client $client, Carrier $carrier, string $coverageZone = 'in'): array
{
    $isInOut = $coverageZone === 'in_out';

    return [
        'class' => 'expat',
        'subclass' => 'Worldwide',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1500.00',
        'source' => 'client',
        'expat' => [
            'coverage_zone' => $coverageZone,
            'travel_scope' => $isInOut ? 'Regional' : null,
            'full_name' => 'Rami Haddad',
            'gender' => 'male',
            'nationality' => 'Lebanese',
            'date_of_birth' => '1988-02-20',
            'phone' => '+96170999888',
        ],
    ];
}

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
        ->patch(route('policies.expat.update', $policy), expatUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('a user gets 404 updating a non-expat policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), expatUpdatePayload($client, $carrier))
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
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.expat.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), expatUpdatePayload($client, $carrier))
        ->assertRedirect(route('policies.expat.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), expatUpdatePayload($client, $carrier));

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

    $payload = expatUpdatePayload($client, $carrier);
    $payload['class'] = 'medical';

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['class']);
});

test('an in-zone policy prohibits a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatUpdatePayload($client, $carrier);
    $payload['expat']['travel_scope'] = 'Worldwide';

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('an in-out zone policy requires a travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatUpdatePayload($client, $carrier, 'in_out');
    unset($payload['expat']['travel_scope']);

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['expat.travel_scope']);
});

test('every canonical expat subclass is accepted', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatUpdatePayload($client, $carrier);
    $payload['subclass'] = $subclass;

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasNoErrors();
})->with(['Worldwide', 'Schengen', 'GCC', 'Student']);

test('a subclass outside the expat list is rejected', function (string $subclass) {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = expatUpdatePayload($client, $carrier);
    $payload['subclass'] = $subclass;

    $this->actingAs($user)
        ->patch(route('policies.expat.update', $policy), $payload)
        ->assertSessionHasErrors(['subclass']);
})->with(['In', 'Pilgrim']);
