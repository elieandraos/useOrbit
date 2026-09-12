<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\Policy;
use App\Models\PolicyFireDetails;
use App\Models\User;

test('fire creates a policy with a correctly linked fire detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    expect($policy->fireDetails)->toBeInstanceOf(PolicyFireDetails::class)
        ->and($policy->fireDetails->policy_id)->toBe($policy->id)
        ->and($policy->fireDetails->country)->toBeInstanceOf(Country::class);
});

test('fireDetails returns null for a non-fire policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => 'medical']);

    expect($policy->fireDetails)->toBeNull();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    expect($policy->fireDetails->policy)->toBeInstanceOf(Policy::class)
        ->and($policy->fireDetails->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its fire detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $detailsId = $policy->fireDetails->id;

    $policy->forceDelete();

    expect(PolicyFireDetails::query()->find($detailsId))->toBeNull();
});
