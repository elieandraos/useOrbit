<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\Policy;
use App\Models\PolicyExpatDetails;
use App\Models\User;

test('expat creates a policy with a correctly linked expat detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);

    expect($policy->expatDetails)->toBeInstanceOf(PolicyExpatDetails::class)
        ->and($policy->expatDetails->policy_id)->toBe($policy->id)
        ->and($policy->expatDetails->country)->toBeInstanceOf(Country::class);
});

test('expatDetails returns null for a non-expat policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => 'medical']);

    expect($policy->expatDetails)->toBeNull();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);

    expect($policy->expatDetails->policy)->toBeInstanceOf(Policy::class)
        ->and($policy->expatDetails->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its expat detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $detailsId = $policy->expatDetails->id;

    $policy->forceDelete();

    expect(PolicyExpatDetails::query()->find($detailsId))->toBeNull();
});
