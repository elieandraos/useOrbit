<?php

declare(strict_types=1);

use App\Models\Organization;
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

test('a user can view a policy from their own organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($user->can('view', $policy))->toBeTrue();
});

test('a user cannot view a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($user->can('view', $policy))->toBeFalse();
});

test('a user with a current organization can create policies', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('create', Policy::class))->toBeTrue();
});

test('a user with no organization cannot create policies', function () {
    $user = User::factory()->make(['organization_id' => null]);

    expect($user->can('create', Policy::class))->toBeFalse();
});

test('a user can update a policy from their own organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($user->can('update', $policy))->toBeTrue();
});

test('a user cannot update a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($user->can('update', $policy))->toBeFalse();
});
