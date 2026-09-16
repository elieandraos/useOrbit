<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyAutomotiveAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;

function thirdPartyAutomotiveAttributes(Client $client, Carrier $carrier): array
{
    return [
        'policy_number' => null,
        'class' => 'automotive',
        'subclass' => 'Third Party Liability',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '800.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'automotive' => [
            'plate_number' => '123 AB',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2022,
            'vin' => null,
            'color' => null,
            'valuation_amount' => null,
            'valuation_source' => null,
        ],
    ];
}

function allRiskAutomotiveAttributes(Client $client, Carrier $carrier): array
{
    return [
        'policy_number' => null,
        'class' => 'automotive',
        'subclass' => 'All Risk',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '2400.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'owner',
        'automotive' => [
            'plate_number' => '456 CD',
            'make' => 'BMW',
            'model' => 'X5',
            'year' => 2023,
            'vin' => '1HGCM82633A123456',
            'color' => 'Black',
            'valuation_amount' => '65000.00',
            'valuation_source' => 'Carrier assessor',
        ],
    ];
}

test('a third party policy stores a vehicle detail row with no valuation', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAutomotiveAction::class)->handle($user, thirdPartyAutomotiveAttributes($client, $carrier));

    expect($policy->automotiveDetails->plate_number)->toBe('123 AB')
        ->and($policy->automotiveDetails->make)->toBe('Toyota')
        ->and($policy->automotiveDetails->valuation_amount)->toBeNull()
        ->and($policy->automotiveDetails->valuation_source)->toBeNull();
});

test('an all risk policy stores its vehicle valuation', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAutomotiveAction::class)->handle($user, allRiskAutomotiveAttributes($client, $carrier));

    expect($policy->automotiveDetails->valuation_amount)->toBe('65000.00')
        ->and($policy->automotiveDetails->valuation_source)->toBe('Carrier assessor');
});

test('a missing required vehicle field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = thirdPartyAutomotiveAttributes($client, $carrier);
    $attributes['automotive']['plate_number'] = null;

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyAutomotiveAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
