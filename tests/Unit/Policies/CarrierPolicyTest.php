<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;

test('owner can viewAny, view, create, update, delete, archive, and unarchive carriers in their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $carrier = Carrier::factory()->forOrganization($owner)->create();

    expect($owner->can('viewAny', Carrier::class))->toBeTrue()
        ->and($owner->can('view', $carrier))->toBeTrue()
        ->and($owner->can('create', Carrier::class))->toBeTrue()
        ->and($owner->can('update', $carrier))->toBeTrue()
        ->and($owner->can('delete', $carrier))->toBeTrue()
        ->and($owner->can('archive', $carrier))->toBeTrue()
        ->and($owner->can('unarchive', $carrier))->toBeTrue();
});

test('admin can viewAny, view, create, update, delete, archive, and unarchive carriers in their organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $carrier = Carrier::factory()->forOrganization($admin)->create();

    expect($admin->can('viewAny', Carrier::class))->toBeTrue()
        ->and($admin->can('view', $carrier))->toBeTrue()
        ->and($admin->can('create', Carrier::class))->toBeTrue()
        ->and($admin->can('update', $carrier))->toBeTrue()
        ->and($admin->can('delete', $carrier))->toBeTrue()
        ->and($admin->can('archive', $carrier))->toBeTrue()
        ->and($admin->can('unarchive', $carrier))->toBeTrue();
});

test('member can viewAny, view, create, and update carriers but cannot delete, archive, or unarchive', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->forOrganization($member)->create();

    expect($member->can('viewAny', Carrier::class))->toBeTrue()
        ->and($member->can('view', $carrier))->toBeTrue()
        ->and($member->can('create', Carrier::class))->toBeTrue()
        ->and($member->can('update', $carrier))->toBeTrue()
        ->and($member->can('delete', $carrier))->toBeFalse()
        ->and($member->can('archive', $carrier))->toBeFalse()
        ->and($member->can('unarchive', $carrier))->toBeFalse();
});

test('owner cannot perform any action on a carrier from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();

    expect($owner->can('view', $carrier))->toBeFalse()
        ->and($owner->can('update', $carrier))->toBeFalse()
        ->and($owner->can('delete', $carrier))->toBeFalse()
        ->and($owner->can('archive', $carrier))->toBeFalse()
        ->and($owner->can('unarchive', $carrier))->toBeFalse();
});

test('member cannot perform any action on a carrier from a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();

    expect($member->can('view', $carrier))->toBeFalse()
        ->and($member->can('update', $carrier))->toBeFalse()
        ->and($member->can('delete', $carrier))->toBeFalse()
        ->and($member->can('archive', $carrier))->toBeFalse()
        ->and($member->can('unarchive', $carrier))->toBeFalse();
});
