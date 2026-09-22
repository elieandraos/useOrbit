<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyAutomotiveAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;

function automotiveUpdateAttributes(Client $client, Carrier $carrier, string $subclass = 'Third Party Liability'): array
{
    $isAllRisk = $subclass === 'All Risk';

    return [
        'policy_number' => null,
        'class' => 'automotive',
        'subclass' => $subclass,
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '900.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'automotive' => [
            'plate_number' => '789 EF',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'vin' => null,
            'color' => 'White',
            'valuation_amount' => $isAllRisk ? '40000.00' : null,
            'valuation_source' => $isAllRisk ? 'Market value' : null,
        ],
    ];
}

test('updates the policy_automotive_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $detailsId = $policy->automotiveDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyAutomotiveAction::class)->handle($user, $policy, automotiveUpdateAttributes($client, $carrier));

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
    app(UpdatePolicyAutomotiveAction::class)->handle($user, $policy, automotiveUpdateAttributes($client, $carrier, 'All Risk'));

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

    $attributes = automotiveUpdateAttributes($client, $carrier);
    $attributes['automotive']['plate_number'] = null;

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyAutomotiveAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
