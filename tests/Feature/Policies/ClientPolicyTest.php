<?php

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

test('owner can viewAny, view, create, update, and delete clients in their organization', function () {
    $organization = Organization::factory()->create();
    $owner = makeUserInOrg($organization, OrganizationRole::Owner);
    $client = Client::factory()->create(['organization_id' => $organization->id]);

    expect($owner->can('viewAny', Client::class))->toBeTrue()
        ->and($owner->can('view', $client))->toBeTrue()
        ->and($owner->can('create', Client::class))->toBeTrue()
        ->and($owner->can('update', $client))->toBeTrue()
        ->and($owner->can('delete', $client))->toBeTrue();
});

test('member can viewAny, view, create, and update clients but cannot delete', function () {
    $organization = Organization::factory()->create();
    $member = makeUserInOrg($organization, OrganizationRole::Member);
    $client = Client::factory()->create(['organization_id' => $organization->id]);

    expect($member->can('viewAny', Client::class))->toBeTrue()
        ->and($member->can('view', $client))->toBeTrue()
        ->and($member->can('create', Client::class))->toBeTrue()
        ->and($member->can('update', $client))->toBeTrue()
        ->and($member->can('delete', $client))->toBeFalse();
});

test('owner cannot perform any action on a client from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $owner = makeUserInOrg($organization, OrganizationRole::Owner);
    $client = Client::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($owner->can('view', $client))->toBeFalse()
        ->and($owner->can('update', $client))->toBeFalse()
        ->and($owner->can('delete', $client))->toBeFalse();
});

test('member cannot perform any action on a client from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $member = makeUserInOrg($organization, OrganizationRole::Member);
    $client = Client::factory()->create(['organization_id' => $otherOrganization->id]);

    expect($member->can('view', $client))->toBeFalse()
        ->and($member->can('update', $client))->toBeFalse()
        ->and($member->can('delete', $client))->toBeFalse();
});
