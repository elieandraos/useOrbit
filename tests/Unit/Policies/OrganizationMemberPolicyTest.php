<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;

test('user with a current organization can viewAny members', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('viewAny', OrganizationMember::class))->toBeTrue();
});

test('user without a current organization cannot viewAny members', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->can('viewAny', OrganizationMember::class))->toBeFalse();
});

test('owner can invite members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('invite', OrganizationMember::class))->toBeTrue();
});

test('admin can invite members', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    expect($admin->can('invite', OrganizationMember::class))->toBeTrue();
});

test('member cannot invite members', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($member->can('invite', OrganizationMember::class))->toBeFalse();
});

test('user without a current organization cannot invite members', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->can('invite', OrganizationMember::class))->toBeFalse();
});
