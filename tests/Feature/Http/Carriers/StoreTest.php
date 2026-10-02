<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'name' => 'Bankers Assurance',
    'phone' => '+961 1 423 423',
    'website' => 'bankers.com.lb',
    'branch' => [
        'street' => 'Saloumeh Square',
        'building_floor' => 'Bankers Tower',
        'city' => 'Beirut',
    ],
    'contact' => [
        'name' => 'Lina Karam',
        'role' => 'COO',
        'email' => 'lina.karam@bankers.com.lb',
        'phone' => '+961 3 188 422',
    ],
];

test('guests are redirected to the login page', function () {
    $this->get(route('carriers.create'))
        ->assertRedirect(route('login'));

    $this->post(route('carriers.store'))
        ->assertRedirect(route('login'));
});

test('create page renders for authenticated user', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('carriers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Carriers/Create'));
});

test('create page pre-fills the organization default country for the first branch', function () {
    $organization = Organization::factory()->withLebanonAndUsdDefaults()->create();
    $user = User::factory()->forOrganization($organization)->create();

    $this->actingAs($user)
        ->get(route('carriers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('defaultCountryId', $organization->default_country_id));
});

test('create page passes a null default country when the organization has none', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('carriers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('defaultCountryId', null));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('carriers.store'))
        ->assertSessionHasErrors(['name', 'branch.city', 'contact.name']);
});

test('store redirects to carriers.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('carriers.store'), $validPayload)
        ->assertRedirect(route('carriers.show', Carrier::query()->first()))
        ->assertHasInertiaFlash('success', 'Carrier created.');

    expect(Carrier::query()->count())->toBe(1);
});

test('store creates the branch with the submitted branch and contact fields', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('carriers.store'), $validPayload)
        ->assertRedirect(route('carriers.show', Carrier::query()->first()));

    /** @var Carrier $carrier */
    $carrier = Carrier::query()->first();

    expect($carrier->branches)->toHaveCount(1);
});

test('store fails when branch.city is missing', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('carriers.store'), [
            ...$validPayload,
            'branch' => collect($validPayload['branch'])->except('city')->all(),
        ])
        ->assertSessionHasErrors(['branch.city' => 'The branch city field is required.']);
});

test('store fails when contact.name is missing', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('carriers.store'), [
            ...$validPayload,
            'contact' => collect($validPayload['contact'])->except('name')->all(),
        ])
        ->assertSessionHasErrors(['contact.name' => 'The contact name field is required.']);
});
