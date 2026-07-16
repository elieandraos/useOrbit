<?php

declare(strict_types=1);

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

test('create page passes the Lebanon country id as the default country', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    Country::query()->create(['iso2' => 'FR', 'name' => 'France', 'iso3' => 'FRA', 'phone_code' => '33', 'region' => 'Europe', 'subregion' => 'Western Europe']);

    $this->actingAs($user)
        ->get(route('clients.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Create')
            ->where('defaultCountryId', $lebanon->id)
        );
});

test('create page passes a null default country id when Lebanon is not seeded', function () {
    $user = User::factory()->withOrganization()->create();

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
        ->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'date_of_birth', 'gender', 'enrollment_date', 'lead_source']);
});

test('store redirects to clients.show with toast on success', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
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
