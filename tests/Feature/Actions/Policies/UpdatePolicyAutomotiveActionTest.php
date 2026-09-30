<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyAutomotiveAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('updates the policy_automotive_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $detailsId = $policy->automotiveDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAutomotiveAction::class)->handle($user, $policy, PolicyPayload::automotive($client, $carrier, [
        'automotive' => ['plate_number' => '789 EF', 'make' => 'Honda'],
    ]));

    $fresh = $policy->fresh('automotiveDetails');
    expect($fresh->automotiveDetails->id)->toBe($detailsId)
        ->and($fresh->automotiveDetails->plate_number)->toBe('789 EF')
        ->and($fresh->automotiveDetails->make)->toBe('Honda');
});

test('switching subclass to all risk populates the vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->automotive()->create([
        'created_by' => $user->id,
        'subclass' => 'Third Party Liability',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAutomotiveAction::class)->handle($user, $policy, PolicyPayload::automotiveAllRisk($client, $carrier, [
        'automotive' => ['valuation_amount' => '40000.00', 'valuation_source' => 'Market value'],
    ]));

    $fresh = $policy->fresh('automotiveDetails');
    expect($fresh->automotiveDetails->valuation_amount)->toBe('40000.00')
        ->and($fresh->automotiveDetails->valuation_source)->toBe('Market value');
});

test('an invalid vehicle field leaves the policy and its details unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->automotive()->create([
        'created_by' => $user->id,
        'premium_amount' => '500.00',
    ]);

    $attributes = PolicyPayload::automotive($client, $carrier, ['automotive' => ['plate_number' => null]]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyAutomotiveAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
