<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;

test('user with a current organization can viewAny agents', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('viewAny', Agent::class))->toBeTrue();
});

test('user without a current organization cannot viewAny agents', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->can('viewAny', Agent::class))->toBeFalse();
});

test('user with a current organization can create agents', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('create', Agent::class))->toBeTrue();
});

test('user without a current organization cannot create agents', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->can('create', Agent::class))->toBeFalse();
});

test('user can view an agent from their own organization', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    expect($user->can('view', $agent))->toBeTrue();
});

test('user cannot view an agent from a different organization', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->create();

    expect($user->can('view', $agent))->toBeFalse();
});

test('user can update an agent from their own organization', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    expect($user->can('update', $agent))->toBeTrue();
});

test('user cannot update an agent from a different organization', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->create();

    expect($user->can('update', $agent))->toBeFalse();
});
