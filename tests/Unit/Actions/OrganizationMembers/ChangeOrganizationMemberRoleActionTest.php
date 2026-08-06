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

test('does not change the role in an organization the actor does not belong to', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();
    $otherOrganization = Organization::factory()->create();
    $member->organizations()->attach($otherOrganization, [
        'role' => OrganizationRole::Member->value,
        'status' => 'active',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($owner, $member, ['role' => 'admin']);

    $otherPivot = $member->organizations()->wherePivot('organization_id', $otherOrganization->id)->first()?->pivot;

    expect($otherPivot->role)->toBe(OrganizationRole::Member);
});
