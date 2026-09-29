<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyTravelAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('updates the policy_travel_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $detailsId = $policy->travelDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyTravelAction::class)->handle($user, $policy, PolicyPayload::travel($client, $carrier, [
        'travel' => ['destination' => 'Spain', 'travelers' => 'Amir Haddad', 'coverage_tier' => 'Premium'],
    ]));

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

    $attributes = PolicyPayload::travel($client, $carrier, [
        'premium_amount' => '220.00',
        'travel' => ['destination' => null],
    ]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyTravelAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('150.00');
});
