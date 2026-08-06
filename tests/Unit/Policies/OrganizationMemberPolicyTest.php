<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
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

test('owner can change an active member role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($owner->can('changeRole', [OrganizationMember::class, $member]))->toBeTrue();
});

test('admin can change an active member role', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($admin->can('changeRole', [OrganizationMember::class, $member]))->toBeTrue();
});

test('member cannot change another member role', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();

    expect($member->can('changeRole', [OrganizationMember::class, $otherMember]))->toBeFalse();
});

test('owner cannot change the owner role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherOwner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('changeRole', [OrganizationMember::class, $otherOwner]))->toBeFalse();
});

test('owner cannot change their own role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('changeRole', [OrganizationMember::class, $owner]))->toBeFalse();
});

test('owner cannot change the role of an invited member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->create(['password' => null]);
    $invitee->organizations()->attach($organization, [
        'role' => OrganizationRole::Member->value,
        'status' => OrganizationMemberStatus::Invited->value,
    ]);

    expect($owner->can('changeRole', [OrganizationMember::class, $invitee]))->toBeFalse();
});

test('owner cannot change the role of a member in another organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $memberElsewhere = User::factory()->withOrganization()->create();

    expect($owner->can('changeRole', [OrganizationMember::class, $memberElsewhere]))->toBeFalse();
});
