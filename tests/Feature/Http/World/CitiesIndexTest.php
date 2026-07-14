<?php

declare(strict_types=1);

use App\Models\User;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

test('guests are redirected to the login page', function () {
    $this->get(route('world.cities.index', ['state_id' => 1]))
        ->assertRedirect(route('login'));
});

test('returns cities scoped to the given state', function () {
    $user = User::factory()->withOrganization()->create();
    $country = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    $mountLebanon = State::query()->create(['name' => 'Mount Lebanon', 'country_id' => $country->id]);
    $beirut = State::query()->create(['name' => 'Beirut', 'country_id' => $country->id]);
    City::query()->create(['name' => 'Jounieh', 'state_id' => $mountLebanon->id, 'country_id' => $country->id, 'country_code' => 'LB']);
    City::query()->create(['name' => 'Baabda', 'state_id' => $mountLebanon->id, 'country_id' => $country->id, 'country_code' => 'LB']);
    City::query()->create(['name' => 'Achrafieh', 'state_id' => $beirut->id, 'country_id' => $country->id, 'country_code' => 'LB']);

    $response = $this->actingAs($user)
        ->getJson(route('world.cities.index', ['state_id' => $mountLebanon->id]))
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toHaveCount(2)
        ->and($names)->toContain('Jounieh', 'Baabda')
        ->and($names)->not->toContain('Achrafieh');
});

test('filters cities by search term', function () {
    $user = User::factory()->withOrganization()->create();
    $country = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    $state = State::query()->create(['name' => 'Mount Lebanon', 'country_id' => $country->id]);
    City::query()->create(['name' => 'Jounieh', 'state_id' => $state->id, 'country_id' => $country->id, 'country_code' => 'LB']);
    City::query()->create(['name' => 'Baabda', 'state_id' => $state->id, 'country_id' => $country->id, 'country_code' => 'LB']);

    $response = $this->actingAs($user)
        ->getJson(route('world.cities.index', ['state_id' => $state->id, 'search' => 'joun']))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toEqual(['Jounieh']);
});

test('returns an empty data set when nothing matches', function () {
    $user = User::factory()->withOrganization()->create();
    $country = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    $state = State::query()->create(['name' => 'Mount Lebanon', 'country_id' => $country->id]);
    City::query()->create(['name' => 'Jounieh', 'state_id' => $state->id, 'country_id' => $country->id, 'country_code' => 'LB']);

    $response = $this->actingAs($user)
        ->getJson(route('world.cities.index', ['state_id' => $state->id, 'search' => 'zzz']))
        ->assertOk();

    expect($response->json('data'))->toBe([]);
});

test('caps results at 20 and ranks prefix matches before substring matches', function () {
    $user = User::factory()->withOrganization()->create();
    $country = Country::query()->create(['iso2' => 'US', 'name' => 'United States', 'iso3' => 'USA', 'phone_code' => '1', 'region' => 'Americas', 'subregion' => 'Northern America']);
    $state = State::query()->create(['name' => 'California', 'country_id' => $country->id]);

    // A row that only contains "san" (not a prefix match) should rank after prefix matches.
    City::query()->create(['name' => 'Alsandro', 'state_id' => $state->id, 'country_id' => $country->id, 'country_code' => 'US']);

    for ($i = 1; $i <= 20; $i++) {
        City::query()->create(['name' => sprintf('San City %02d', $i), 'state_id' => $state->id, 'country_id' => $country->id, 'country_code' => 'US']);
    }

    $response = $this->actingAs($user)
        ->getJson(route('world.cities.index', ['state_id' => $state->id, 'search' => 'san']))
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toHaveCount(20)
        ->and($names->first())->toStartWith('San')
        ->and($names)->not->toContain('Alsandro');
});
