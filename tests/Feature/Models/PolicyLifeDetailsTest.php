<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyLifeDetails;
use App\Models\User;

test('life creates a policy with a correctly linked life detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);

    expect($policy->lifeDetails)->toBeInstanceOf(PolicyLifeDetails::class)
        ->and($policy->lifeDetails->policy_id)->toBe($policy->id)
        ->and($policy->lifeDetails->beneficiaries)->toBeString();
});

test('lifeDetails returns null for a non-life policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => 'medical']);

    expect($policy->lifeDetails)->toBeNull();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);

    expect($policy->lifeDetails->policy)->toBeInstanceOf(Policy::class)
        ->and($policy->lifeDetails->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its life detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $detailsId = $policy->lifeDetails->id;

    $policy->forceDelete();

    expect(PolicyLifeDetails::query()->find($detailsId))->toBeNull();
});
