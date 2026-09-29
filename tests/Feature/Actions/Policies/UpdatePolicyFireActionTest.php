<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyFireAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('updates the policy_fire_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $detailsId = $policy->fireDetails->id;
    $state = State::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyFireAction::class)->handle($user, $policy, PolicyPayload::fire($client, $carrier, $state, [
        'fire' => [
            'property_type' => 'Warehouse',
            'floor_area' => 400,
            'street' => 'Sin El Fil Road',
            'building_floor' => null,
            'sum_insured' => '500000.00',
        ],
    ]));

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
    $state = State::factory()->create();

    $attributes = PolicyPayload::fire($client, $carrier, $state, ['fire' => ['street' => null]]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyFireAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
