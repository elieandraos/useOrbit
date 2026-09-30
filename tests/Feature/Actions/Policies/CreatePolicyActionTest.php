<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Tests\Support\PolicyPayload;

test('a submitted policy_number is respected', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::base($client, $carrier, ['policy_number' => 'CUSTOM-001']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, $attributes);

    expect($policy->policy_number)->toBe('CUSTOM-001');
});

test('sets created_by to the user id', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, PolicyPayload::base($client, $carrier));

    expect($policy->created_by)->toBe($user->id);
});

test('creates the policy scoped to the organization context', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    app(OrganizationContext::class)->set($otherOrganization->id);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, PolicyPayload::base($client, $carrier));

    expect($policy->organization_id)->toBe($otherOrganization->id);
});
