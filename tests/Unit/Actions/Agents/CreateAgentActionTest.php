<?php

declare(strict_types=1);

use App\Actions\Agents\CreateAgentAction;
use App\Enums\AgentStatus;
use App\Models\User;

$attributes = [
    'first_name' => 'Mira',
    'last_name' => 'Olsen',
    'date_of_birth' => '1990-04-12',
    'joined_at' => '2020-06-01',
    'phone' => '+961 3 188 422',
    'email' => 'mira.olsen@useorbit.com',
    'street' => 'Rue Gouraud',
    'building_floor' => 'Building Saifi 21, 3rd Floor',
    'city' => 'Beirut',
];

test('sets status to active by default', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $agent = app(CreateAgentAction::class)->handle($user, $attributes);

    expect($agent->status)->toBe(AgentStatus::Active);
});

test('creates agent scoped to the user current organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $agent = app(CreateAgentAction::class)->handle($user, $attributes);

    expect($agent->organization_id)->toBe($user->organization_id);
});

test('sets created_by and updated_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $agent = app(CreateAgentAction::class)->handle($user, $attributes);

    expect($agent->created_by)->toBe($user->id)
        ->and($agent->updated_by)->toBe($user->id);
});

test('stores the submitted attributes', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $agent = app(CreateAgentAction::class)->handle($user, $attributes);

    expect($agent->first_name)->toBe('Mira')
        ->and($agent->last_name)->toBe('Olsen')
        ->and($agent->phone)->toBe('+961 3 188 422')
        ->and($agent->email)->toBe('mira.olsen@useorbit.com')
        ->and($agent->street)->toBe('Rue Gouraud')
        ->and($agent->building_floor)->toBe('Building Saifi 21, 3rd Floor')
        ->and($agent->city)->toBe('Beirut');
});

test('generates a non-empty slug', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $agent = app(CreateAgentAction::class)->handle($user, $attributes);

    expect($agent->slug)->not->toBeEmpty();
});

test('two organizations can each have the same slug without collision', function () use ($attributes) {
    $userA = User::factory()->withOrganization()->create();
    $userB = User::factory()->withOrganization()->create();

    setOrganizationContext($userA);
    /** @noinspection PhpUnhandledExceptionInspection */
    $agentA = app(CreateAgentAction::class)->handle($userA, $attributes);

    setOrganizationContext($userB);
    /** @noinspection PhpUnhandledExceptionInspection */
    $agentB = app(CreateAgentAction::class)->handle($userB, $attributes);

    expect($agentA->slug)->toBe('mira-olsen')
        ->and($agentB->slug)->toBe('mira-olsen');
});

test('appends counter when slug already exists in the same organization', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    $first = app(CreateAgentAction::class)->handle($user, $attributes);
    /** @noinspection PhpUnhandledExceptionInspection */
    $second = app(CreateAgentAction::class)->handle($user, $attributes);

    expect($first->slug)->toBe('mira-olsen')
        ->and($second->slug)->toBe('mira-olsen-1');
});
