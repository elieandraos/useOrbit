<?php

declare(strict_types=1);

use App\Enums\ClientType;
use App\Enums\EmergencyContactRelationship;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Models\Client;
use App\Models\Country;
use App\Models\State;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('clients.create'))
        ->assertRedirect(route('login'));

    $this->post(route('clients.store'))
        ->assertRedirect(route('login'));
});

test('create page renders for authenticated user', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Clients/Create'));
});

test('create page passes the acting user country id as the default country', function () {
    $country = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    $user = User::factory()->withOrganization()->create(['country_id' => $country->id]);

    $this->actingAs($user)
        ->get(route('clients.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Create')
            ->where('defaultCountryId', $country->id)
        );
});

test('create page passes a null default country id when the acting user has none set', function () {
    $user = User::factory()->withOrganization()->create(['country_id' => null]);

    $this->actingAs($user)
        ->get(route('clients.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Create')
            ->where('defaultCountryId', null)
        );
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'))
        ->assertSessionHasErrors(['client_type', 'first_name', 'last_name', 'phone', 'date_of_birth', 'gender', 'enrollment_date', 'lead_source']);
});

test('store redirects to clients.show with toast on success', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Individual->value,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-0100',
            'date_of_birth' => '1990-01-15',
            'gender' => Gender::Male->value,
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Referral->value,
        ])
        ->assertRedirect(route('clients.show', Client::query()->first()))
        ->assertHasInertiaFlash('success', 'Client created.');

    expect(Client::query()->count())->toBe(1);
});

test('store persists state_id and city on the client', function () {
    $user = User::factory()->withOrganization()->create();
    $country = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    $state = State::query()->create(['name' => 'Mount Lebanon', 'country_id' => $country->id]);

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Individual->value,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '555-0100',
            'date_of_birth' => '1990-01-15',
            'gender' => Gender::Male->value,
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Referral->value,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city' => 'Jounieh',
        ])
        ->assertRedirect(route('clients.show', Client::query()->first()));

    /** @var Client $client */
    $client = Client::query()->first();
    expect($client->state_id)->toBe($state->id)
        ->and($client->city)->toBe('Jounieh');
});

test('store creates a company client without date of birth or gender', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Company->value,
            'company_name' => 'Acme Logistics',
            'first_name' => 'Rita',
            'last_name' => 'Haddad',
            'phone' => '555-0300',
            'email' => 'rita@acme.test',
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Website->value,
        ])
        ->assertRedirect(route('clients.show', Client::query()->first()));

    /** @var Client $client */
    $client = Client::query()->first();
    expect($client->client_type)->toBe(ClientType::Company)
        ->and($client->company_name)->toBe('Acme Logistics')
        ->and($client->date_of_birth)->toBeNull()
        ->and($client->gender)->toBeNull();
});

test('store fails when company_name is missing for a company client', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Company->value,
            'first_name' => 'Rita',
            'last_name' => 'Haddad',
            'phone' => '555-0300',
            'email' => 'rita@acme.test',
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Website->value,
        ])
        ->assertSessionHasErrors(['company_name']);
});

test('store fails when email is missing for a company client', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Company->value,
            'company_name' => 'Acme Logistics',
            'first_name' => 'Rita',
            'last_name' => 'Haddad',
            'phone' => '555-0300',
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Website->value,
        ])
        ->assertSessionHasErrors(['email']);
});

test('store fails when date of birth, gender, or mothers name are present for a company client', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Company->value,
            'company_name' => 'Acme Logistics',
            'first_name' => 'Rita',
            'last_name' => 'Haddad',
            'phone' => '555-0300',
            'email' => 'rita@acme.test',
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Website->value,
            'date_of_birth' => '1990-01-15',
            'gender' => Gender::Female->value,
            'mothers_name' => 'Mary',
        ])
        ->assertSessionHasErrors(['date_of_birth', 'gender', 'mothers_name']);
});

test('store fails when emergency contact fields are present for a company client', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'client_type' => ClientType::Company->value,
            'company_name' => 'Acme Logistics',
            'first_name' => 'Rita',
            'last_name' => 'Haddad',
            'phone' => '555-0300',
            'email' => 'rita@acme.test',
            'enrollment_date' => '2024-01-01',
            'lead_source' => LeadSource::Website->value,
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_relationship' => EmergencyContactRelationship::Spouse->value,
            'emergency_contact_phone' => '555-0400',
        ])
        ->assertSessionHasErrors(['emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone']);
});
