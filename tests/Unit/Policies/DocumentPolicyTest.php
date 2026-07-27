<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;

test('user with a current organization can viewAny, view, and create documents for a documentable', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->for($organization)->create();
    $document = Document::factory()->forOrganization($user)->create();

    expect($user->can('viewAny', [Document::class, $client]))->toBeTrue()
        ->and($user->can('view', $document))->toBeTrue()
        ->and($user->can('create', [Document::class, $client]))->toBeTrue();
});

test('user cannot viewAny, view, or create documents for a documentable or document from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->for($otherOrganization)->create();
    $document = Document::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($user->can('viewAny', [Document::class, $client]))->toBeFalse()
        ->and($user->can('view', $document))->toBeFalse()
        ->and($user->can('create', [Document::class, $client]))->toBeFalse();
});

test('owner can delete a completed document uploaded by another member of their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $document = Document::factory()->forOrganization($member)->uploadedBy($member)->completed()->create();

    expect($owner->can('delete', $document))->toBeTrue();
});

test('member can delete their own completed document', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $document = Document::factory()->forOrganization($member)->uploadedBy($member)->completed()->create();

    expect($member->can('delete', $document))->toBeTrue();
});

test('member cannot delete a completed document uploaded by another member', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherMember = User::factory()->forOrganization($organization)->create();
    $document = Document::factory()->forOrganization($otherMember)->uploadedBy($otherMember)->completed()->create();

    expect($member->can('delete', $document))->toBeFalse();
});

test('delete is denied while the document is pending, even for the owner or the uploader', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $uploader = User::factory()->forOrganization($organization)->create();
    $document = Document::factory()->forOrganization($uploader)->uploadedBy($uploader)->create();

    expect($owner->can('delete', $document))->toBeFalse()
        ->and($uploader->can('delete', $document))->toBeFalse();
});

test('delete is denied while the document is processing, even for the owner or the uploader', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $uploader = User::factory()->forOrganization($organization)->create();
    $document = Document::factory()->forOrganization($uploader)->uploadedBy($uploader)->processing()->create();

    expect($owner->can('delete', $document))->toBeFalse()
        ->and($uploader->can('delete', $document))->toBeFalse();
});

test('owner cannot delete a document from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $document = Document::factory()->completed()->create(['organization_id' => $otherOrganization->id]);

    expect($owner->can('delete', $document))->toBeFalse();
});

test('user with a current organization can finalize a document batch', function () {
    $user = User::factory()->withOrganization()->create();

    expect($user->can('finalize', Document::class))->toBeTrue();
});

test('user without a current organization cannot finalize a document batch', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->can('finalize', Document::class))->toBeFalse();
});
