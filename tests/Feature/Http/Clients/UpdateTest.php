<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\EmergencyContactRelationship;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'phone' => '555-0200',
    'date_of_birth' => '1992-05-20',
    'gender' => Gender::Female->value,
    'enrollment_date' => '2024-06-01',
    'lead_source' => LeadSource::Referral->value,
    'status' => ClientStatus::Active->value,
];

$companyPayload = [
    'company_name' => 'Acme Logistics',
    'first_name' => 'Rita',
    'last_name' => 'Haddad',
    'phone' => '555-0300',
    'email' => 'rita@acme.test',
    'enrollment_date' => '2024-06-01',
    'lead_source' => LeadSource::Website->value,
];

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.edit', $client))->assertRedirect(route('login'));
    $this->patch(route('clients.update', $client))->assertRedirect(route('login'));
});

test('edit page renders with client data', function () {
    $user = User::factory()->withOrganization()->create();
    $editor = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create([
        'updated_by' => $editor->id,
    ]);

    $this->actingAs($user)
        ->get(route('clients.edit', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Edit')
            ->hasResource('client', ClientResource::make($client->load(['updatedBy', 'country', 'state'])))
        );
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client))
        ->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'date_of_birth', 'gender', 'enrollment_date', 'lead_source']);
});

test('update succeeds without a status field and preserves the existing status', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create([
        'status' => ClientStatus::Archived->value,
    ]);

    $this->actingAs($user)
        ->patch(route('clients.update', $client), collect($validPayload)->except('status')->all())
        ->assertRedirect(route('clients.show', $client->fresh()));

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->status)->toBe(ClientStatus::Archived);
});

test('update redirects to clients.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), $validPayload)
        ->assertRedirect(route('clients.show', $client->fresh()))
        ->assertHasInertiaFlash('success', 'Client updated.');
});

test('user gets 404 when updating a client from another organization', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), $validPayload)
        ->assertNotFound();
});

test('update succeeds for a company client without date of birth or gender', function () use ($companyPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), $companyPayload)
        ->assertRedirect(route('clients.show', $client->fresh()));

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->company_name)->toBe('Acme Logistics')
        ->and($fresh->date_of_birth)->toBeNull()
        ->and($fresh->gender)->toBeNull();
});

test('update fails when company_name is missing for a company client', function () use ($companyPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), collect($companyPayload)->except('company_name')->all())
        ->assertSessionHasErrors(['company_name']);
});

test('update fails when email is missing for a company client', function () use ($companyPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), collect($companyPayload)->except('email')->all())
        ->assertSessionHasErrors(['email']);
});

test('update fails when date of birth, gender, or mothers name are present for a company client', function () use ($companyPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), [
            ...$companyPayload,
            'date_of_birth' => '1990-01-15',
            'gender' => Gender::Female->value,
            'mothers_name' => 'Mary',
        ])
        ->assertSessionHasErrors(['date_of_birth', 'gender', 'mothers_name']);
});

test('update fails when emergency contact fields are present for a company client', function () use ($companyPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), [
            ...$companyPayload,
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_relationship' => EmergencyContactRelationship::Spouse->value,
            'emergency_contact_phone' => '555-0400',
        ])
        ->assertSessionHasErrors(['emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone']);
});

test('client_type never changes via update regardless of what is submitted', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('clients.update', $client), [
            ...$validPayload,
            'client_type' => ClientType::Company->value,
        ])
        ->assertRedirect(route('clients.show', $client->fresh()));

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->client_type)->toBe(ClientType::Individual);
});
