<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;

test('user with a current organization can viewAny and create tags', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('viewAny', Tag::class))->toBeTrue()
        ->and($user->can('create', Tag::class))->toBeTrue();
});

test('user without a current organization cannot viewAny or create tags', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->can('viewAny', Tag::class))->toBeFalse()
        ->and($user->can('create', Tag::class))->toBeFalse();
});

test('creator can delete their own tag', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($member)->createdBy($member)->create();

    expect($member->can('delete', $tag))->toBeTrue();
});

test('owner can delete a tag created by another member of their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($member)->createdBy($member)->create();

    expect($owner->can('delete', $tag))->toBeTrue();
});

test('member cannot delete a tag created by another member', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($otherMember)->createdBy($otherMember)->create();

    expect($member->can('delete', $tag))->toBeFalse();
});

test('member from another organization cannot delete the tag', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $creator = User::factory()->forOrganization($organization)->create();
    $outsider = User::factory()->forOrganization($otherOrganization)->create();
    $tag = Tag::factory()->forOrganization($creator)->createdBy($creator)->create();

    expect($outsider->can('delete', $tag))->toBeFalse();
});

test('creator can update their own tag', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($member)->createdBy($member)->create();

    expect($member->can('update', $tag))->toBeTrue();
});

test('owner can update a tag created by another member of their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($member)->createdBy($member)->create();

    expect($owner->can('update', $tag))->toBeTrue();
});

test('member cannot update a tag created by another member', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $tag = Tag::factory()->forOrganization($otherMember)->createdBy($otherMember)->create();

    expect($member->can('update', $tag))->toBeFalse();
});

test('member from another organization cannot update the tag', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $creator = User::factory()->forOrganization($organization)->create();
    $outsider = User::factory()->forOrganization($otherOrganization)->create();
    $tag = Tag::factory()->forOrganization($creator)->createdBy($creator)->create();

    expect($outsider->can('update', $tag))->toBeFalse();
});
