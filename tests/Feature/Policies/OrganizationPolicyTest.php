<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('owner can manage the two factor requirement', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('update', Organization::class))->toBeTrue();
});

test('admin cannot manage the two factor requirement', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    expect($admin->cannot('update', Organization::class))->toBeTrue();
});

test('member cannot manage the two factor requirement', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($member->cannot('update', Organization::class))->toBeTrue();
});
