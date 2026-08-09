<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\ChangeOrganizationMemberRoleAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('updates the member role', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ChangeOrganizationMemberRoleAction::class)->handle($member, ['role' => 'admin']);

    expect($member->fresh()->role)->toBe(OrganizationRole::Admin);
});
