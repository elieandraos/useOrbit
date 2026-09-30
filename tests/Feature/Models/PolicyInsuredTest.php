<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;

test('the factory produces a valid insured linked to a policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $insured = PolicyInsured::factory()->for($policy)->create();

    expect($insured->policy_id)->toBe($policy->id)
        ->and($insured->full_name)->toBeString()
        ->and($policy->insureds)->toHaveCount(1)
        ->and($policy->insureds->first()->is($insured))->toBeTrue();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $insured = PolicyInsured::factory()->for($policy)->create();

    expect($insured->policy)->toBeInstanceOf(Policy::class)
        ->and($insured->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its insureds', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $insured = PolicyInsured::factory()->for($policy)->create();

    $policy->forceDelete();

    expect(PolicyInsured::query()->find($insured->id))->toBeNull();
});
