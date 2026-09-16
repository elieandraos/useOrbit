<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyFireAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\QueryException;

function fireUpdateAttributes(Client $client, Carrier $carrier, int $stateId, int $countryId): array
{
    return [
        'policy_number' => null,
        'class' => 'fire',
        'subclass' => 'Standard',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '700.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'fire' => [
            'property_type' => 'Warehouse',
            'floor_area' => 400,
            'year_built' => 1998,
            'street' => 'Sin El Fil Road',
            'building_floor' => null,
            'city' => 'Beirut',
            'state_id' => $stateId,
            'country_id' => $countryId,
            'sum_insured' => '500000.00',
        ],
    ];
}

function fireUpdateLocation(): array
{
    $country = Country::query()->firstOrCreate(
        ['iso2' => 'LB'],
        ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia'],
    );

    $state = State::query()->firstOrCreate(['country_id' => $country->id, 'name' => 'Beirut']);

    return [$state->id, $country->id];
}

test('updates the policy_fire_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $detailsId = $policy->fireDetails->id;
    [$stateId, $countryId] = fireUpdateLocation();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyFireAction::class)->handle($user, $policy, fireUpdateAttributes($client, $carrier, $stateId, $countryId));

    $fresh = $policy->fresh('fireDetails');
    expect($fresh->fireDetails->id)->toBe($detailsId)
        ->and($fresh->fireDetails->property_type)->toBe('Warehouse')
        ->and($fresh->fireDetails->floor_area)->toBe(400)
        ->and($fresh->fireDetails->street)->toBe('Sin El Fil Road')
        ->and($fresh->fireDetails->building_floor)->toBeNull()
        ->and($fresh->fireDetails->sum_insured)->toBe('500000.00');
});

test('an invalid fire field leaves the policy and its details unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'premium_amount' => '500.00',
    ]);
    [$stateId, $countryId] = fireUpdateLocation();

    $attributes = fireUpdateAttributes($client, $carrier, $stateId, $countryId);
    $attributes['fire']['street'] = null;

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyFireAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
