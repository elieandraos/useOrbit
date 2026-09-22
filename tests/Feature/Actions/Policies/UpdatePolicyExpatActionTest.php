<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyExpatAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;

function expatUpdateAttributes(Client $client, Carrier $carrier, string $coverageZone = 'in'): array
{
    $isInOut = $coverageZone === 'in_out';

    return [
        'policy_number' => null,
        'class' => 'expat',
        'subclass' => $isInOut ? 'In-Out' : 'In',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '1500.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'expat' => [
            'coverage_zone' => $coverageZone,
            'travel_scope' => $isInOut ? 'Regional' : null,
            'full_name' => 'Rami Haddad',
            'gender' => 'male',
            'nationality' => 'Lebanese',
            'date_of_birth' => '1988-02-20',
            'phone' => '+96170999888',
            'country_id' => null,
            'visa_expiry_date' => $isInOut ? '2027-03-01' : null,
        ],
    ];
}

test('updates the policy_expat_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $detailsId = $policy->expatDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyExpatAction::class)->handle($user, $policy, expatUpdateAttributes($client, $carrier));

    $fresh = $policy->fresh('expatDetails');
    expect($fresh->expatDetails->id)->toBe($detailsId)
        ->and($fresh->expatDetails->full_name)->toBe('Rami Haddad')
        ->and($fresh->expatDetails->coverage_zone->value)->toBe('in');
});

test('switching to the in-out zone populates the travel scope and visa expiry', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'subclass' => 'In',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyExpatAction::class)->handle($user, $policy, expatUpdateAttributes($client, $carrier, 'in_out'));

    $fresh = $policy->fresh('expatDetails');
    expect($fresh->expatDetails->travel_scope)->toBe('Regional')
        ->and($fresh->expatDetails->visa_expiry_date->format('Y-m-d'))->toBe('2027-03-01');
});

test('an invalid expat field leaves the policy and its details unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'premium_amount' => '500.00',
    ]);

    $attributes = expatUpdateAttributes($client, $carrier);
    $attributes['expat']['full_name'] = null;

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyExpatAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
