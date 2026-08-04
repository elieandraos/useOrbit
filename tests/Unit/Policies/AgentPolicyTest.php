<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Agent;
use App\Models\Organization;
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

test('owner can archive an agent from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->forOrganization($owner)->create();

    expect($owner->can('archive', $agent))->toBeTrue();
});

test('non-owner member cannot archive an agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    expect($user->can('archive', $agent))->toBeFalse();
});

test('owner cannot archive an agent from a different organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->create();

    expect($owner->can('archive', $agent))->toBeFalse();
});

test('owner can unarchive an agent from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->forOrganization($owner)->archived()->create();

    expect($owner->can('unarchive', $agent))->toBeTrue();
});

test('non-owner member cannot unarchive an agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->archived()->create();

    expect($user->can('unarchive', $agent))->toBeFalse();
});

test('owner cannot unarchive an agent from a different organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->archived()->create();

    expect($owner->can('unarchive', $agent))->toBeFalse();
});

test('owner can delete an agent from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->forOrganization($owner)->create();

    expect($owner->can('delete', $agent))->toBeTrue();
});

test('non-owner member cannot delete an agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    expect($user->can('delete', $agent))->toBeFalse();
});

test('owner cannot delete an agent from a different organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->create();

    expect($owner->can('delete', $agent))->toBeFalse();
});
