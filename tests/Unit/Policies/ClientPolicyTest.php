<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('owner can viewAny, view, create, update, delete, archive, and unarchive clients in their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
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
    $member = User::factory()->forOrganization($organization)->create();
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
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
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
    $member = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->for($otherOrganization)->create();

    expect($member->can('view', $client))->toBeFalse()
        ->and($member->can('update', $client))->toBeFalse()
        ->and($member->can('delete', $client))->toBeFalse()
        ->and($member->can('archive', $client))->toBeFalse()
        ->and($member->can('unarchive', $client))->toBeFalse();
});
