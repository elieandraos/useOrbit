<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;

function basePolicyAttributes(Client $client, Carrier $carrier): array
{
    return [
        'policy_number' => null,
        'class' => 'medical',
        'subclass' => 'In',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1200.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
    ];
}

test('a blank policy_number is auto-generated', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAction::class)->handle($user, basePolicyAttributes($client, $carrier));

    expect($policy->policy_number)->toBe('POL-0001');
});

test('auto-generated policy numbers increment per organization', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $first = app(CreatePolicyAction::class)->handle($user, basePolicyAttributes($client, $carrier));
    /** @noinspection PhpUnhandledExceptionInspection */
    $second = app(CreatePolicyAction::class)->handle($user, basePolicyAttributes($client, $carrier));

    expect($first->policy_number)->toBe('POL-0001')
        ->and($second->policy_number)->toBe('POL-0002');
});

test('a submitted policy_number is respected', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = basePolicyAttributes($client, $carrier);
    $attributes['policy_number'] = 'CUSTOM-001';

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
    $policy = app(CreatePolicyAction::class)->handle($user, basePolicyAttributes($client, $carrier));

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
    $policy = app(CreatePolicyAction::class)->handle($user, basePolicyAttributes($client, $carrier));

    expect($policy->organization_id)->toBe($otherOrganization->id);
});
