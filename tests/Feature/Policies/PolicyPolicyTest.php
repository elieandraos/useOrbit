<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\User;

test('a user with a current organization can viewAny policies', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('viewAny', Policy::class))->toBeTrue();
});

test('a user with no organization cannot viewAny policies', function () {
    $user = User::factory()->make(['organization_id' => null]);

    expect($user->can('viewAny', Policy::class))->toBeFalse();
});

test('a user with a current organization can create policies', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('create', Policy::class))->toBeTrue();
});

test('a user with no organization cannot create policies', function () {
    $user = User::factory()->make(['organization_id' => null]);

    expect($user->can('create', Policy::class))->toBeFalse();
});
