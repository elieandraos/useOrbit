<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyAutomotiveDetails;
use App\Models\User;

test('automotive creates a policy with a correctly linked automotive detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    expect($policy->automotiveDetails)->toBeInstanceOf(PolicyAutomotiveDetails::class)
        ->and($policy->automotiveDetails->policy_id)->toBe($policy->id);
});

test('automotiveDetails returns null for a non-automotive policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => 'medical']);

    expect($policy->automotiveDetails)->toBeNull();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    expect($policy->automotiveDetails->policy)->toBeInstanceOf(Policy::class)
        ->and($policy->automotiveDetails->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its automotive detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $detailsId = $policy->automotiveDetails->id;

    $policy->forceDelete();

    expect(PolicyAutomotiveDetails::query()->find($detailsId))->toBeNull();
});
