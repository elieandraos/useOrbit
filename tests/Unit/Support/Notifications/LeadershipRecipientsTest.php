<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Support\Notifications\LeadershipRecipients;
use App\Support\Tenancy\OrganizationContext;

test('resolve returns owners and admins, excluding members, invited, inactive, and other organizations', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    User::factory()->forOrganization($organization, OrganizationRole::Admin)->create(['status' => OrganizationMemberStatus::Invited]);
    User::factory()->forOrganization($organization, OrganizationRole::Admin)->create(['status' => OrganizationMemberStatus::Suspended]);
    User::factory()->withOrganization()->create(['role' => OrganizationRole::Owner]);
    app(OrganizationContext::class)->set($organization->id);

    $recipients = app(LeadershipRecipients::class)->resolve();

    expect($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$owner->id, $admin->id])->sort()->values()->all());
});

test('resolve excludes the passed-in users', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    app(OrganizationContext::class)->set($organization->id);

    $recipients = app(LeadershipRecipients::class)->resolve($owner);

    expect($recipients->pluck('id')->all())->toBe([$admin->id]);
});
