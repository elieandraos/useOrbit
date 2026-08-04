<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;

$validPayload = [
    'first_name' => 'Mira',
    'last_name' => 'Olsen',
    'date_of_birth' => '1990-04-12',
    'phone' => '+961 3 188 422',
    'email' => 'mira.olsen@useorbit.com',
    'street' => 'Rue Gouraud',
    'building_floor' => 'Building Saifi 21, 3rd Floor',
    'city' => 'Beirut',
];

test('guests are redirected to the login page', function () {
    $this->get(route('agents.create'))
        ->assertRedirect(route('login'));

    $this->post(route('agents.store'))
        ->assertRedirect(route('login'));
});

test('create page renders for authenticated user', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('agents.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Agents/Create'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('agents.store'))
        ->assertSessionHasErrors(['first_name', 'last_name', 'date_of_birth', 'phone', 'email']);
});

test('store redirects to agents.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('agents.store'), $validPayload)
        ->assertRedirect(route('agents.show', Agent::query()->first()))
        ->assertHasInertiaFlash('success', 'Agent created.');

    expect(Agent::query()->count())->toBe(1);
});

test('store creates the agent with the submitted fields', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('agents.store'), $validPayload)
        ->assertRedirect(route('agents.show', Agent::query()->first()));

    /** @var Agent $agent */
    $agent = Agent::query()->first();

    expect($agent->first_name)->toBe('Mira')
        ->and($agent->last_name)->toBe('Olsen')
        ->and($agent->email)->toBe('mira.olsen@useorbit.com')
        ->and($agent->city)->toBe('Beirut');
});
