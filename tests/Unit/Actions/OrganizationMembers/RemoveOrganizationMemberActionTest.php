<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\RemoveOrganizationMemberAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('detaches the member from the actor organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member);

    $pivot = $member->organizations()->wherePivot('organization_id', $organization->id)->first()?->pivot;

    expect($pivot)->toBeNull();
});

test('clears the member current_organization_id when it matches the actor organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $member = app(RemoveOrganizationMemberAction::class)->handle($owner, $member);

    expect($member->current_organization_id)->toBeNull();
});

test('keeps the User row intact', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member);

    expect(User::query()->find($member->id))->not->toBeNull();
});

test('does not detach the member from an organization the actor does not belong to', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherOrganization = Organization::factory()->create();
    $member->organizations()->attach($otherOrganization, [
        'role' => OrganizationRole::Member->value,
        'status' => 'active',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(RemoveOrganizationMemberAction::class)->handle($owner, $member);

    $otherPivot = $member->organizations()->wherePivot('organization_id', $otherOrganization->id)->first()?->pivot;

    expect($otherPivot)->not->toBeNull();
});
