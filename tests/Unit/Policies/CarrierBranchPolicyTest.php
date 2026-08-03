<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

test('member can create, update, and delete a branch belonging to their organization', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->forOrganization($member)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    expect($member->can('create', [CarrierBranch::class, $carrier]))->toBeTrue()
        ->and($member->can('update', $branch))->toBeTrue()
        ->and($member->can('delete', $branch))->toBeTrue();
});

test('user cannot create, update, or delete a branch belonging to a different organization', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    expect($user->can('create', [CarrierBranch::class, $carrier]))->toBeFalse()
        ->and($user->can('update', $branch))->toBeFalse()
        ->and($user->can('delete', $branch))->toBeFalse();
});
