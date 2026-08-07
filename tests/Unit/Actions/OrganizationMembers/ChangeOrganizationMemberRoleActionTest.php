<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\ChangeOrganizationMemberRoleAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('updates the member role scoped to the actor organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'admin']);

    $pivot = $member->organizations()->wherePivot('organization_id', $organization->id)->first()?->pivot;

    expect($pivot->role)->toBe(OrganizationRole::Admin);
});

test('does not change the role for a member outside the actor organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $otherOrganization = Organization::factory()->create();
    $member = User::factory()->forOrganization($otherOrganization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'admin']);

    $pivot = $member->organizations()->wherePivot('organization_id', $otherOrganization->id)->first()?->pivot;

    expect($pivot->role)->toBe(OrganizationRole::Member);
});
