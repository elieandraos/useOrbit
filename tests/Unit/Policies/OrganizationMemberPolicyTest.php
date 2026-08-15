<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('user with a current organization can viewAny members', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('viewAny', User::class))->toBeTrue();
});

test('owner can manage organization members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('manage', User::class))->toBeTrue();
});

test('admin can manage organization members', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    expect($admin->can('manage', User::class))->toBeTrue();
});

test('member cannot manage organization members', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($member->can('manage', User::class))->toBeFalse();
});

test('owner can invite members', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('invite', User::class))->toBeTrue();
});

test('admin can invite members', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    expect($admin->can('invite', User::class))->toBeTrue();
});

test('member cannot invite members', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($member->can('invite', User::class))->toBeFalse();
});

test('owner can change an active member role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($owner->can('changeRole', [User::class, $member]))->toBeTrue();
});

test('admin can change an active member role', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($admin->can('changeRole', [User::class, $member]))->toBeTrue();
});

test('member cannot change another member role', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();

    expect($member->can('changeRole', [User::class, $otherMember]))->toBeFalse();
});

test('owner cannot change the owner role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherOwner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('changeRole', [User::class, $otherOwner]))->toBeFalse();
});

test('owner cannot change their own role', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('changeRole', [User::class, $owner]))->toBeFalse();
});

test('owner cannot change the role of an invited member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    expect($owner->can('changeRole', [User::class, $invitee]))->toBeFalse();
});

test('owner cannot change the role of a member in another organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $memberElsewhere = User::factory()->withOrganization()->create();

    expect($owner->can('changeRole', [User::class, $memberElsewhere]))->toBeFalse();
});

test('owner can remove an active member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($owner->can('remove', [User::class, $member]))->toBeTrue();
});

test('admin can remove an active member', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($admin->can('remove', [User::class, $member]))->toBeTrue();
});

test('member cannot remove another member', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();

    expect($member->can('remove', [User::class, $otherMember]))->toBeFalse();
});

test('owner cannot remove the owner', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherOwner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('remove', [User::class, $otherOwner]))->toBeFalse();
});

test('owner cannot remove themselves', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    expect($owner->can('remove', [User::class, $owner]))->toBeFalse();
});

test('owner cannot remove an invited member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    expect($owner->can('remove', [User::class, $invitee]))->toBeFalse();
});

test('owner cannot remove a member in another organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $memberElsewhere = User::factory()->withOrganization()->create();

    expect($owner->can('remove', [User::class, $memberElsewhere]))->toBeFalse();
});

test('owner can revoke a pending invitation', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    expect($owner->can('revoke', [User::class, $invitee]))->toBeTrue();
});

test('admin can revoke a pending invitation', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    expect($admin->can('revoke', [User::class, $invitee]))->toBeTrue();
});

test('member cannot revoke a pending invitation', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $invitee = User::factory()->forOrganization($organization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    expect($member->can('revoke', [User::class, $invitee]))->toBeFalse();
});

test('owner cannot revoke an active member', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    expect($owner->can('revoke', [User::class, $member]))->toBeFalse();
});

test('owner cannot revoke an invitation in another organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $otherOrganization = Organization::factory()->create();
    $invitee = User::factory()->forOrganization($otherOrganization)->create([
        'password' => null,
        'status' => OrganizationMemberStatus::Invited,
    ]);

    expect($owner->can('revoke', [User::class, $invitee]))->toBeFalse();
});
