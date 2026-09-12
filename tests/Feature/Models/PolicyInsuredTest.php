<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Database\QueryException;

test('the factory produces a valid insured linked to a policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $insured = PolicyInsured::factory()->for($policy)->create();

    expect($insured->policy_id)->toBe($policy->id)
        ->and($insured->member_code)->toBeString()
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

test('two insureds on the same policy cannot share a member_code', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);

    PolicyInsured::factory()->for($policy)->create(['member_code' => 'MBR-001']);
})->throws(QueryException::class);

test('the same member_code is allowed across different policies', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policyOne */
    $policyOne = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    /** @var Policy $policyTwo */
    $policyTwo = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $insuredOne = PolicyInsured::factory()->for($policyOne)->create(['member_code' => 'MBR-001']);
    $insuredTwo = PolicyInsured::factory()->for($policyTwo)->create(['member_code' => 'MBR-001']);

    expect($insuredOne->member_code)->toBe($insuredTwo->member_code)
        ->and($insuredOne->policy_id)->not->toBe($insuredTwo->policy_id);
});

test('deleting the policy deletes its insureds', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $insured = PolicyInsured::factory()->for($policy)->create();

    $policy->forceDelete();

    expect(PolicyInsured::query()->find($insured->id))->toBeNull();
});
