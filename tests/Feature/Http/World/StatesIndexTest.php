<?php

declare(strict_types=1);

use App\Models\User;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

test('guests are redirected to the login page', function () {
    $this->get(route('world.states.index', ['country_id' => 1]))
        ->assertRedirect(route('login'));
});

test('returns states scoped to the given country', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    $france = Country::query()->create(['iso2' => 'FR', 'name' => 'France', 'iso3' => 'FRA', 'phone_code' => '33', 'region' => 'Europe', 'subregion' => 'Western Europe']);
    $mountLebanon = State::query()->create(['name' => 'Mount Lebanon', 'country_id' => $lebanon->id]);
    State::query()->create(['name' => 'Beirut', 'country_id' => $lebanon->id]);
    State::query()->create(['name' => 'Île-de-France', 'country_id' => $france->id]);

    $response = $this->actingAs($user)
        ->getJson(route('world.states.index', ['country_id' => $lebanon->id]))
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toHaveCount(2)
        ->and($names)->toContain('Mount Lebanon', $mountLebanon->name)
        ->and($names)->not->toContain('Île-de-France');
});

test('filters states by search term', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    State::query()->create(['name' => 'Mount Lebanon', 'country_id' => $lebanon->id]);
    State::query()->create(['name' => 'Beirut', 'country_id' => $lebanon->id]);

    $response = $this->actingAs($user)
        ->getJson(route('world.states.index', ['country_id' => $lebanon->id, 'search' => 'moun']))
        ->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())->toEqual(['Mount Lebanon']);
});

test('returns an empty data set when nothing matches', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);
    State::query()->create(['name' => 'Beirut', 'country_id' => $lebanon->id]);

    $response = $this->actingAs($user)
        ->getJson(route('world.states.index', ['country_id' => $lebanon->id, 'search' => 'zzz']))
        ->assertOk();

    expect($response->json('data'))->toBe([]);
});

test('caps results at 20 and ranks prefix matches before substring matches', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->create(['iso2' => 'LB', 'name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);

    // A row that only contains "leb" (not a prefix match) should rank after prefix matches.
    State::query()->create(['name' => 'New Lebtown', 'country_id' => $lebanon->id]);

    for ($i = 1; $i <= 20; $i++) {
        State::query()->create(['name' => sprintf('Lebanon State %02d', $i), 'country_id' => $lebanon->id]);
    }

    $response = $this->actingAs($user)
        ->getJson(route('world.states.index', ['country_id' => $lebanon->id, 'search' => 'leb']))
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toHaveCount(20)
        ->and($names->first())->toStartWith('Lebanon')
        ->and($names)->not->toContain('New Lebtown');
});
