<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;

function singleUpdatePayload(Client $client, Carrier $carrier): array
{
    return [
        'class' => 'medical',
        'subclass' => 'In',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1500.00',
        'source' => 'client',
        'medical' => [
            'coverage_scope' => 'in',
            'class_tier' => 'class_a',
            'co_insurance' => false,
            'guaranteed_renewable' => true,
            'insured_full_name' => 'Amelia Hartwell',
            'insured_date_of_birth' => '1986-03-22',
            'insured_gender' => 'female',
            'insured_smoker' => false,
        ],
    ];
}

function groupUpdatePayload(Client $client, Carrier $carrier, array $insureds = []): array
{
    return [
        'class' => 'medical',
        'subclass' => 'In-Out',
        'type' => 'group',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '28400.00',
        'source' => 'owner',
        'medical' => [
            'coverage_scope' => 'in_out',
            'class_tier' => 'class_b',
            'co_insurance' => false,
            'guaranteed_renewable' => true,
        ],
        'insureds' => $insureds,
    ];
}

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create(['type' => 'single']);

    $this->patch(route('policies.update', $policy))
        ->assertRedirect(route('login'));
});

test('a user gets 404 updating a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create(['organization_id' => $otherOrganization->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.update', $policy), singleUpdatePayload($client, $carrier))
        ->assertNotFound();
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);

    $this->actingAs($user)
        ->patch(route('policies.update', $policy))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('update redirects to policies.show with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.update', $policy), singleUpdatePayload($client, $carrier))
        ->assertRedirect(route('policies.show', $policy->fresh()))
        ->assertHasInertiaFlash('success', 'Policy updated.');
});

test('update wires the submitted client and carrier onto the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('policies.update', $policy), singleUpdatePayload($client, $carrier));

    $this->assertDatabaseHas('policies', [
        'id' => $policy->id,
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
});

test('an insureds.*.id belonging to another policy is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $otherPolicy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $otherMember = PolicyInsured::factory()->for($otherPolicy)->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = groupUpdatePayload($client, $carrier, [
        ['id' => $otherMember->id, 'full_name' => 'Someone', 'relationship' => 'Spouse', 'date_of_birth' => '1988-08-08'],
    ]);

    $this->actingAs($user)
        ->patch(route('policies.update', $policy), $payload)
        ->assertSessionHasErrors(['insureds.0.id']);
});

test('a group policy requires an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'group']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = groupUpdatePayload($client, $carrier);
    unset($payload['insureds']);

    $this->actingAs($user)
        ->patch(route('policies.update', $policy), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a single policy prohibits an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id, 'type' => 'single']);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = singleUpdatePayload($client, $carrier);
    $payload['insureds'] = [['full_name' => 'Extra', 'relationship' => 'Child', 'date_of_birth' => '2020-01-01']];

    $this->actingAs($user)
        ->patch(route('policies.update', $policy), $payload)
        ->assertSessionHasErrors(['insureds']);
});
