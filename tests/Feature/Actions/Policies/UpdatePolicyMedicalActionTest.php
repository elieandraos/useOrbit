<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyMedicalAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('updates the policy_medical_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
    ]);
    $detailsId = $policy->medicalDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyMedicalAction::class)->handle($user, $policy, PolicyPayload::medicalSingle($client, $carrier, [
        'medical' => ['insured_full_name' => 'Updated Name'],
    ]));

    $fresh = $policy->fresh('medicalDetails');
    expect($fresh->medicalDetails->id)->toBe($detailsId)
        ->and($fresh->medicalDetails->insured_full_name)->toBe('Updated Name');
});

test('an existing member id updates that row in place instead of replacing it', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    $member = PolicyInsured::factory()->for($policy)->create([
        'full_name' => 'Original Name',
    ]);

    $attributes = PolicyPayload::medicalGroup($client, $carrier, [
        'insureds' => [PolicyPayload::insured(['id' => $member->id, 'full_name' => 'Renamed Member'])],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyMedicalAction::class)->handle($user, $policy, $attributes);

    expect($policy->insureds)->toHaveCount(1)
        ->and($policy->insureds[0]->id)->toBe($member->id)
        ->and($policy->insureds[0]->full_name)->toBe('Renamed Member');
});

test('omitting a previously-existing member removes it', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    $kept = PolicyInsured::factory()->for($policy)->create();
    $removed = PolicyInsured::factory()->for($policy)->create();

    $attributes = PolicyPayload::medicalGroup($client, $carrier, [
        'insureds' => [PolicyPayload::insured(['id' => $kept->id])],
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyMedicalAction::class)->handle($user, $policy, $attributes);

    expect($policy->insureds)->toHaveCount(1)
        ->and($policy->insureds->pluck('id'))->not->toContain($removed->id);
});

test('switching type away from group removes all insureds', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
    ]);
    PolicyInsured::factory()->for($policy)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyMedicalAction::class)->handle($user, $policy, PolicyPayload::medicalSingle($client, $carrier));

    expect($policy->insureds()->count())->toBe(0);
});

test('an invalid member leaves the policy, its details, and its insureds unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'group',
        'premium_amount' => '1000.00',
    ]);
    $kept = PolicyInsured::factory()->for($policy)->create();

    $attributes = PolicyPayload::medicalGroup($client, $carrier, [
        'insureds' => [
            PolicyPayload::insured(['id' => $kept->id]),
            PolicyPayload::insured(['full_name' => 'Invalid Member', 'date_of_birth' => null]),
        ],
    ]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyMedicalAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('1000.00')
        ->and(PolicyInsured::query()->whereKey($kept->id)->exists())->toBeTrue()
        ->and(PolicyInsured::query()->count())->toBe(1);
});
