<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Tests\Support\PolicyPayload;

test('updates the policy fields in the database', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'premium_amount' => '1000.00',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, PolicyPayload::base($client, $carrier, ['premium_amount' => '1500.00']));

    $fresh = $policy->fresh();
    expect($fresh->premium_amount)->toBe('1500.00')
        ->and($fresh->client_id)->toBe($client->id)
        ->and($fresh->carrier_id)->toBe($carrier->id);
});

test('sets updated_by to the user id', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, PolicyPayload::base($client, $carrier));

    expect($policy->fresh()->updated_by)->toBe($user->id);
});

test('regenerates the slug when policy_number changes', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'policy_number' => 'POL-0001',
        'slug' => 'pol-0001',
    ]);

    $attributes = PolicyPayload::base($client, $carrier, ['policy_number' => 'POL-9999']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    expect($policy->fresh()->slug)->toBe('pol-9999');
});

test('keeps the existing slug when policy_number does not change', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => 'single',
        'policy_number' => 'POL-0001',
        'slug' => 'pol-0001',
    ]);

    $attributes = PolicyPayload::base($client, $carrier, ['policy_number' => 'POL-0001']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAction::class)->handle($user, $policy, $attributes);

    expect($policy->fresh()->slug)->toBe('pol-0001');
});
