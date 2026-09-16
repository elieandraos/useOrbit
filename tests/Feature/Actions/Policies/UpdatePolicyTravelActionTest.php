<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyTravelAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;

function travelUpdateAttributes(Client $client, Carrier $carrier): array
{
    return [
        'policy_number' => null,
        'class' => 'travel',
        'subclass' => 'Premium',
        'type' => 'single',
        'client_id' => $client->id,
        'carrier_id' => $carrier->id,
        'agent_id' => null,
        'effective_date' => '2026-01-01',
        'expiry_date' => '2027-01-01',
        'premium_amount' => '220.00',
        'discount_amount' => null,
        'status' => 'active',
        'source' => 'client',
        'travel' => [
            'destination' => 'Spain',
            'trip_start_date' => '2026-07-01',
            'trip_end_date' => '2026-07-10',
            'travelers' => 'Amir Haddad',
            'coverage_tier' => 'Premium',
        ],
    ];
}

test('updates the policy_travel_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $detailsId = $policy->travelDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyTravelAction::class)->handle($user, $policy, travelUpdateAttributes($client, $carrier));

    $fresh = $policy->fresh('travelDetails');
    expect($fresh->travelDetails->id)->toBe($detailsId)
        ->and($fresh->travelDetails->destination)->toBe('Spain')
        ->and($fresh->travelDetails->travelers)->toBe('Amir Haddad')
        ->and($fresh->travelDetails->coverage_tier)->toBe('Premium');
});

test('an invalid trip field leaves the policy and its details unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->travel()->create([
        'created_by' => $user->id,
        'premium_amount' => '150.00',
    ]);

    $attributes = travelUpdateAttributes($client, $carrier);
    $attributes['travel']['destination'] = null;

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyTravelAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('150.00');
});
