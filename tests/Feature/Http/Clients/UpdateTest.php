<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
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

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.edit', $client))->assertRedirect(route('login'));
    $this->patch(route('clients.update', $client))->assertRedirect(route('login'));
});

test('edit page renders with client data', function () {
    $user = User::factory()->withOrganization()->create();
    $editor = User::factory()->withOrganization()->create();
    $client = Client::factory()->create([
        'organization_id' => $user->current_organization_id,
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
    $client = Client::factory()->create(['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->patch(route('clients.update', $client))
        ->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'date_of_birth', 'gender', 'enrollment_date', 'lead_source']);
});

test('update succeeds without a status field and preserves the existing status', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->create([
        'organization_id' => $user->current_organization_id,
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
    $client = Client::factory()->create(['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->patch(route('clients.update', $client), $validPayload)
        ->assertRedirect(route('clients.show', $client->fresh()))
        ->assertHasInertiaFlash('success', 'Client updated.');
});

test('user gets 404 when updating a client from another organization', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->patch(route('clients.update', $client), $validPayload)
        ->assertNotFound();
});
