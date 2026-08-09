<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\RemoveOrganizationMemberAction;
use App\Enums\OrganizationRole;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;

test('reassigns authored records to the successor scoped to the organization', function (string $modelClass, string $column) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $successor = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    $record = $modelClass::factory()->forOrganization($owner)->create([$column => $member->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member, $successor);

    expect($record->fresh()->{$column})->toBe($successor->id);
})->with([
    'client created_by' => [Client::class, 'created_by'],
    'client updated_by' => [Client::class, 'updated_by'],
    'document uploaded_by' => [Document::class, 'uploaded_by'],
    'note created_by' => [Note::class, 'created_by'],
    'tag created_by' => [Tag::class, 'created_by'],
    'tag updated_by' => [Tag::class, 'updated_by'],
    'carrier created_by' => [Carrier::class, 'created_by'],
    'carrier updated_by' => [Carrier::class, 'updated_by'],
    'agent created_by' => [Agent::class, 'created_by'],
    'agent updated_by' => [Agent::class, 'updated_by'],
]);

test('reassigns created_by on a soft-deleted record via withTrashed', function (string $modelClass) {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $successor = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    $record = $modelClass::factory()->forOrganization($owner)->create(['created_by' => $member->id]);
    $record->delete();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member, $successor);

    $fresh = $record->fresh();

    expect($fresh->trashed())->toBeTrue()
        ->and($fresh->created_by)->toBe($successor->id);
})->with([
    'client' => Client::class,
    'carrier' => Carrier::class,
    'agent' => Agent::class,
]);

test('hard-deletes the member row', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $successor = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member, $successor);

    expect(User::query()->find($member->id))->toBeNull();
});

test('cascades the pivot deletion', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $successor = User::factory()->forOrganization($organization)->create();
    app(OrganizationContext::class)->set($organization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member, $successor);

    expect($organization->fresh()->users()->count())->toBe(2);
});
