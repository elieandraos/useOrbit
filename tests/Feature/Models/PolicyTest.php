<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;

test('createdBy resolves the user who created the policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($policy->createdBy)->toBeInstanceOf(User::class)
        ->and($policy->createdBy->is($user))->toBeTrue();
});

test('updatedBy resolves the user who last updated the policy', function () {
    $user = User::factory()->withOrganization()->create();
    $updater = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'updated_by' => $updater->id]);

    expect($policy->updatedBy)->toBeInstanceOf(User::class)
        ->and($policy->updatedBy->is($updater))->toBeTrue();
});

test('client resolves the insured client', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'client_id' => $client->id]);

    expect($policy->client)->toBeInstanceOf(Client::class)
        ->and($policy->client->is($client))->toBeTrue();
});

test('carrier resolves the underwriting carrier', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id]);

    expect($policy->carrier)->toBeInstanceOf(Carrier::class)
        ->and($policy->carrier->is($carrier))->toBeTrue();
});

test('the factory produces a valid, persistable policy', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Policy $policy */
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($policy->exists)->toBeTrue()
        ->and($policy->fresh())->not->toBeNull();
});

test('documentableName returns the policy number', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($policy->documentableName())->toBe($policy->policy_number);
});

test('notificationSubjectName returns the policy number', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($policy->notificationSubjectName())->toBe($policy->policy_number);
});
