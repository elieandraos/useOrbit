<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationMembers\ActiveOrganizationRecipients;
use App\Support\Tenancy\OrganizationContext;

test('query returns only active users scoped to the current organization', function () {
    $organization = Organization::factory()->create();
    $activeMember = User::factory()->forOrganization($organization)->create();
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Invited]);
    User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Suspended]);
    User::factory()->withOrganization()->create();
    app(OrganizationContext::class)->set($organization->id);

    $recipients = app(ActiveOrganizationRecipients::class)->query()->get();

    expect($recipients)->toHaveCount(1)
        ->and($recipients->first()->id)->toBe($activeMember->id);
});
