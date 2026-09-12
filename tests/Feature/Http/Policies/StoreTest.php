<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;

function singlePayload(Client $client, Carrier $carrier): array
{
    return [
        'class' => 'medical',
        'subclass' => 'In',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1200.00',
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

function groupPayload(Client $client, Carrier $carrier): array
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
        'insureds' => [
            ['full_name' => 'Lina Hartwell', 'relationship' => 'Spouse', 'date_of_birth' => '1988-08-08', 'gender' => 'female'],
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $this->post(route('policies.store'))
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('policies.store'))
        ->assertSessionHasErrors(['class', 'subclass', 'type', 'client_id', 'carrier_id', 'effective_date', 'expiry_date', 'premium_amount', 'source']);
});

test('store redirects to policies.index with a toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.store'), singlePayload($client, $carrier))
        ->assertRedirect(route('policies.index'))
        ->assertHasInertiaFlash('success', 'Policy created.');

    expect(Policy::query()->count())->toBe(1);
});

test('store wires the submitted client and carrier onto the created policy', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('policies.store'), singlePayload($client, $carrier))
        ->assertRedirect(route('policies.index'));

    $this->assertDatabaseHas('policies', [
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
    ]);
});

test('a group policy requires an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = groupPayload($client, $carrier);
    unset($payload['insureds']);

    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a single policy prohibits an insureds array', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = singlePayload($client, $carrier);
    $payload['insureds'] = [['full_name' => 'Extra', 'relationship' => 'Child', 'date_of_birth' => '2020-01-01']];

    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasErrors(['insureds']);
});

test('a client belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $otherClient = Client::factory()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = singlePayload($otherClient, $carrier);

    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasErrors(['client_id']);
});

test('a carrier belonging to a different organization is rejected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherCarrier = Carrier::factory()->create();

    $payload = singlePayload($client, $otherCarrier);

    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasErrors(['carrier_id']);
});

test('a single policy is rejected when the insured profile fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = singlePayload($client, $carrier);
    unset($payload['medical']['insured_full_name'], $payload['medical']['insured_date_of_birth'], $payload['medical']['insured_gender'], $payload['medical']['insured_smoker']);

    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasErrors(['medical.insured_full_name', 'medical.insured_date_of_birth', 'medical.insured_gender', 'medical.insured_smoker']);
});

test('a co_insurance_share is required when co_insurance is true', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = singlePayload($client, $carrier);
    $payload['medical']['co_insurance'] = true;

    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasErrors(['medical.co_insurance_share']);
});

test('a co_insurance_share of 15 is accepted when co_insurance is true', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $payload = singlePayload($client, $carrier);
    $payload['medical']['co_insurance'] = true;
    $payload['medical']['co_insurance_share'] = 15;

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->post(route('policies.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('policies.index'));
});
