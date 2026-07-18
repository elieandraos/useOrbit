<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

function makeUserInOrg(Organization $organization, OrganizationRole $role): User
{
    $user = User::factory()->create(['current_organization_id' => $organization->id]);
    $user->organizations()->attach($organization, [
        'role' => $role->value,
        'status' => OrganizationMemberStatus::Active->value,
    ]);

    return $user;
}

test('owner can viewAny, view, create, update, delete, archive, and unarchive clients in their organization', function () {
    $organization = Organization::factory()->create();
    $owner = makeUserInOrg($organization, OrganizationRole::Owner);
    $client = Client::factory()->forOrganization($owner)->create();

    expect($owner->can('viewAny', Client::class))->toBeTrue()
        ->and($owner->can('view', $client))->toBeTrue()
        ->and($owner->can('create', Client::class))->toBeTrue()
        ->and($owner->can('update', $client))->toBeTrue()
        ->and($owner->can('delete', $client))->toBeTrue()
        ->and($owner->can('archive', $client))->toBeTrue()
        ->and($owner->can('unarchive', $client))->toBeTrue();
});

test('member can viewAny, view, create, and update clients but cannot delete, archive, or unarchive', function () {
    $organization = Organization::factory()->create();
    $member = makeUserInOrg($organization, OrganizationRole::Member);
    $client = Client::factory()->forOrganization($member)->create();

    expect($member->can('viewAny', Client::class))->toBeTrue()
        ->and($member->can('view', $client))->toBeTrue()
        ->and($member->can('create', Client::class))->toBeTrue()
        ->and($member->can('update', $client))->toBeTrue()
        ->and($member->can('delete', $client))->toBeFalse()
        ->and($member->can('archive', $client))->toBeFalse()
        ->and($member->can('unarchive', $client))->toBeFalse();
});

test('owner cannot perform any action on a client from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $owner = makeUserInOrg($organization, OrganizationRole::Owner);
    $client = Client::factory()->for($otherOrganization)->create();

    expect($owner->can('view', $client))->toBeFalse()
        ->and($owner->can('update', $client))->toBeFalse()
        ->and($owner->can('delete', $client))->toBeFalse()
        ->and($owner->can('archive', $client))->toBeFalse()
        ->and($owner->can('unarchive', $client))->toBeFalse();
});

test('member cannot perform any action on a client from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $member = makeUserInOrg($organization, OrganizationRole::Member);
    $client = Client::factory()->for($otherOrganization)->create();

    expect($member->can('view', $client))->toBeFalse()
        ->and($member->can('update', $client))->toBeFalse()
        ->and($member->can('delete', $client))->toBeFalse()
        ->and($member->can('archive', $client))->toBeFalse()
        ->and($member->can('unarchive', $client))->toBeFalse();
});
