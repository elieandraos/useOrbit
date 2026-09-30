<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\User;

test('the create page offers the canonical fire subclasses', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Create')
            ->where('subclasses', ['Building', 'Contents', 'Business interruption', 'All risk'])
        );
});

test('the create page receives the shared and fire form options', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PolicyFire/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'subclasses', 'countries'])
        );
});

test('the create page offers only the configured market countries', function () {
    $user = User::factory()->withOrganization()->create();
    $lebanon = Country::query()->firstOrCreate(['iso2' => 'LB'], ['name' => 'Lebanon', 'iso3' => 'LBN']);
    Country::factory()->create();

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('countries', 1)
            ->where('countries.0.id', $lebanon->id)
        );
});

test('no country is offered when no market is configured', function () {
    config(['markets.countries' => []]);
    $user = User::factory()->withOrganization()->create();
    Country::query()->firstOrCreate(['iso2' => 'LB'], ['name' => 'Lebanon', 'iso3' => 'LBN']);

    $this->actingAs($user)
        ->get(route('policies.fire.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('countries', 0));
});
