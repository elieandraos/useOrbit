<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Exports\ClientsExport;
use App\Models\Client;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

test('headings returns the export column labels', function () {
    $export = new ClientsExport([], null, 'asc');

    expect($export->headings())->toBe([
        'Name',
        'Email',
        'Phone',
        'Gender',
        'Date of Birth',
        'Age',
        'Address',
        'Enrollment Date',
        'Lead Source',
        'Status',
    ]);
});

test('map transforms a client into an export row', function () {
    /** @var Country $country */
    $country = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    /** @var State $state */
    $state = State::query()->create(['name' => 'Beirut', 'country_id' => $country->id]);
    /** @var City $city */
    $city = City::query()->create(['name' => 'Achrafieh', 'state_id' => $state->id, 'country_id' => $country->id, 'country_code' => 'LB']);

    /** @var Client $client */
    $client = Client::factory()->create([
        'first_name' => 'Aline',
        'middle_name' => 'Yusuf',
        'last_name' => 'Haddad',
        'email' => 'aline@example.com',
        'phone' => '+96170123456',
        'gender' => Gender::Female->value,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'street' => '12 Main Street',
        'building_floor' => '3rd Floor',
        'city_id' => $city->id,
        'state_id' => $state->id,
        'country_id' => $country->id,
        'enrollment_date' => '2024-01-10',
        'lead_source' => LeadSource::Referral->value,
        'status' => ClientStatus::Active->value,
    ])->load(['country', 'state', 'city']);

    $export = new ClientsExport([], null, 'asc');

    expect($export->map($client))->toBe([
        'Aline Haddad',
        'aline@example.com',
        '+96170123456',
        'Female',
        now()->subYears(30)->toDateString(),
        30,
        '12 Main Street, 3rd Floor, Achrafieh, Beirut, Lebanon',
        '2024-01-10',
        'Referral',
        'Active',
    ]);
});

test('map omits blank address parts', function () {
    /** @var Client $client */
    $client = Client::factory()->create([
        'building_floor' => null,
        'country_id' => null,
    ])->load(['country', 'state', 'city']);

    $export = new ClientsExport([], null, 'asc');
    $row = $export->map($client);

    expect($row[6])->toBe(collect([$client->street, $client->city?->name, $client->state?->name])->filter()->implode(', '));
});
