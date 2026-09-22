<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyExpatAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;

function inZoneExpatAttributes(Client $client, Carrier $carrier, ?int $countryId = null): array
{
    return [
        'policy_number' => null,
        'class' => 'expat',
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
        'expat' => [
            'coverage_zone' => 'in',
            'travel_scope' => null,
            'full_name' => 'Karim Saad',
            'gender' => 'male',
            'nationality' => 'Lebanese',
            'date_of_birth' => '1985-04-12',
            'phone' => '+96170123456',
            'country_id' => $countryId,
            'visa_expiry_date' => null,
        ],
    ];
}

function inOutZoneExpatAttributes(Client $client, Carrier $carrier, ?int $countryId = null): array
{
    return [
        'policy_number' => null,
        'class' => 'expat',
        'subclass' => 'In-Out',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '3200.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'owner',
        'expat' => [
            'coverage_zone' => 'in_out',
            'travel_scope' => 'Worldwide',
            'full_name' => 'Nadine Fares',
            'gender' => 'female',
            'nationality' => 'Lebanese',
            'date_of_birth' => '1990-09-03',
            'phone' => '+96170654321',
            'country_id' => $countryId,
            'visa_expiry_date' => '2027-06-01',
        ],
    ];
}

test('an in-zone policy stores an expat detail row with no travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyExpatAction::class)->handle($user, inZoneExpatAttributes($client, $carrier));

    expect($policy->expatDetails->full_name)->toBe('Karim Saad')
        ->and($policy->expatDetails->coverage_zone->value)->toBe('in')
        ->and($policy->expatDetails->travel_scope)->toBeNull();
});

test('an in-out zone policy stores its travel scope and visa expiry', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $country = Country::query()->firstOrCreate(['iso2' => 'LB'], ['name' => 'Lebanon', 'iso3' => 'LBN', 'phone_code' => '961', 'region' => 'Asia', 'subregion' => 'Western Asia']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyExpatAction::class)->handle($user, inOutZoneExpatAttributes($client, $carrier, $country->id));

    expect($policy->expatDetails->travel_scope)->toBe('Worldwide')
        ->and($policy->expatDetails->country_id)->toBe($country->id)
        ->and($policy->expatDetails->visa_expiry_date->format('Y-m-d'))->toBe('2027-06-01');
});

test('a missing required expat field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = inZoneExpatAttributes($client, $carrier);
    $attributes['expat']['full_name'] = null;

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyExpatAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
