<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;

test('user with a current organization can viewAny, view, and create notes for a notable', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->for($organization)->create();
    $note = Note::factory()->forOrganization($user)->create();

    expect($user->can('viewAny', [Note::class, $client]))->toBeTrue()
        ->and($user->can('view', $note))->toBeTrue()
        ->and($user->can('create', [Note::class, $client]))->toBeTrue();
});

test('user cannot viewAny, view, or create notes for a notable or note from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->for($otherOrganization)->create();
    $note = Note::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($user->can('viewAny', [Note::class, $client]))->toBeFalse()
        ->and($user->can('view', $note))->toBeFalse()
        ->and($user->can('create', [Note::class, $client]))->toBeFalse();
});

test('author can update and delete their own note', function () {
    $organization = Organization::factory()->create();
    $author = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($author)->createdBy($author)->create();

    expect($author->can('update', $note))->toBeTrue()
        ->and($author->can('delete', $note))->toBeTrue();
});

test('owner can update and delete a note authored by another member of their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($member)->createdBy($member)->create();

    expect($owner->can('update', $note))->toBeTrue()
        ->and($owner->can('delete', $note))->toBeTrue();
});

test('member cannot update or delete a note authored by another member', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $note = Note::factory()->forOrganization($otherMember)->createdBy($otherMember)->create();

    expect($member->can('update', $note))->toBeFalse()
        ->and($member->can('delete', $note))->toBeFalse();
});

test('owner cannot update or delete a note from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $note = Note::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($owner->can('update', $note))->toBeFalse()
        ->and($owner->can('delete', $note))->toBeFalse();
});
