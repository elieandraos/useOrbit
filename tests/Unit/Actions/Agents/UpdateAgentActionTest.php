<?php

declare(strict_types=1);

use App\Actions\Agents\UpdateAgentAction;
use App\Models\Agent;
use App\Models\User;

$attributes = [
    'first_name' => 'Nadia',
    'last_name' => 'Fares',
    'date_of_birth' => '1988-09-02',
    'phone' => '+961 3 555 555',
    'email' => 'nadia.fares@useorbit.com',
    'street' => 'Hamra Street',
    'building_floor' => 'Block 12',
    'city' => 'Beirut',
];

test('updates the agent fields in the database', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateAgentAction::class)->handle($user, $agent, $attributes);

    /** @var Agent $fresh */
    $fresh = $agent->fresh();
    expect($fresh->first_name)->toBe('Nadia')
        ->and($fresh->last_name)->toBe('Fares')
        ->and($fresh->phone)->toBe('+961 3 555 555')
        ->and($fresh->email)->toBe('nadia.fares@useorbit.com')
        ->and($fresh->city)->toBe('Beirut');
});

test('sets updated_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateAgentAction::class)->handle($user, $agent, $attributes);

    /** @var Agent $fresh */
    $fresh = $agent->fresh();
    expect($fresh->updated_by)->toBe($user->id);
});

test('regenerates slug when the name changes', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create([
        'first_name' => 'Mira',
        'last_name' => 'Olsen',
        'slug' => 'mira-olsen',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateAgentAction::class)->handle($user, $agent, $attributes);

    /** @var Agent $fresh */
    $fresh = $agent->fresh();
    expect($fresh->slug)->toBe('nadia-fares');
});

test('keeps existing slug when the name does not change', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create([
        'first_name' => 'Nadia',
        'last_name' => 'Fares',
        'slug' => 'nadia-fares',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateAgentAction::class)->handle($user, $agent, $attributes);

    /** @var Agent $fresh */
    $fresh = $agent->fresh();
    expect($fresh->slug)->toBe('nadia-fares');
});
