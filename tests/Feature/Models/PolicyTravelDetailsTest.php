<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyTravelDetails;
use App\Models\User;

test('travel creates a policy with a correctly linked travel detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);

    expect($policy->travelDetails)->toBeInstanceOf(PolicyTravelDetails::class)
        ->and($policy->travelDetails->policy_id)->toBe($policy->id)
        ->and($policy->travelDetails->coverage_tier)->toBe($policy->subclass)
        ->and($policy->travelDetails->travelers)->toBeString();
});

test('travelDetails returns null for a non-travel policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => 'medical']);

    expect($policy->travelDetails)->toBeNull();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);

    expect($policy->travelDetails->policy)->toBeInstanceOf(Policy::class)
        ->and($policy->travelDetails->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its travel detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $detailsId = $policy->travelDetails->id;

    $policy->forceDelete();

    expect(PolicyTravelDetails::query()->find($detailsId))->toBeNull();
});
