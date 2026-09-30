<?php

declare(strict_types=1);

use App\Enums\PolicyType;
use App\Models\Policy;
use App\Models\PolicyMedicalDetails;
use App\Models\User;

test('medical creates a policy with a correctly linked medical detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    expect($policy->medicalDetails)->toBeInstanceOf(PolicyMedicalDetails::class)
        ->and($policy->medicalDetails->policy_id)->toBe($policy->id);
});

test('medical leaves the insured profile empty for a group policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group->value,
    ]);

    expect($policy->medicalDetails->only([
        'insured_full_name',
        'insured_date_of_birth',
        'insured_gender',
        'insured_smoker',
        'insured_medical_history',
    ]))->each->toBeNull();
});

test('medical fills the insured profile for a single policy without covered members', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Single->value,
    ]);

    expect($policy->medicalDetails->only([
        'insured_full_name',
        'insured_date_of_birth',
        'insured_gender',
        'insured_smoker',
    ]))->each->not->toBeNull()
        ->and($policy->insureds)->toBeEmpty();
});

test('medicalDetails returns null for a non-medical policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'class' => 'automotive']);

    expect($policy->medicalDetails)->toBeNull();
});

test('policy resolves the parent policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    expect($policy->medicalDetails->policy)->toBeInstanceOf(Policy::class)
        ->and($policy->medicalDetails->policy->is($policy))->toBeTrue();
});

test('deleting the policy deletes its medical detail row', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $detailsId = $policy->medicalDetails->id;

    $policy->forceDelete();

    expect(PolicyMedicalDetails::query()->find($detailsId))->toBeNull();
});
