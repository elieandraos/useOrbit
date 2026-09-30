<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyFireAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('a fire policy stores a property detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyFireAction::class)->handle($user, PolicyPayload::fire($client, $carrier, $state, [
        'fire' => [
            'property_type' => 'Residential apartment',
            'floor_area' => 180,
            'year_built' => 2005,
            'street' => 'Hamra Street',
            'building_floor' => 'Floor 3',
            'city' => 'Beirut',
            'sum_insured' => '250000.00',
        ],
    ]));

    expect($policy->fireDetails->property_type)->toBe('Residential apartment')
        ->and($policy->fireDetails->floor_area)->toBe(180)
        ->and($policy->fireDetails->year_built)->toBe(2005)
        ->and($policy->fireDetails->street)->toBe('Hamra Street')
        ->and($policy->fireDetails->building_floor)->toBe('Floor 3')
        ->and($policy->fireDetails->city)->toBe('Beirut')
        ->and($policy->fireDetails->state_id)->toBe($state->id)
        ->and($policy->fireDetails->country_id)->toBe($state->country_id)
        ->and($policy->fireDetails->sum_insured)->toBe('250000.00');
});

test('a fire policy accepts a null year built', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    $attributes = PolicyPayload::fire($client, $carrier, $state, ['fire' => ['year_built' => null]]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyFireAction::class)->handle($user, $attributes);

    expect($policy->fireDetails->year_built)->toBeNull();
});

test('no policy_insureds row is created for a fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyFireAction::class)->handle($user, PolicyPayload::fire($client, $carrier, $state));

    $this->assertDatabaseCount('policy_insureds', 0);
    expect($policy->fireDetails)->not->toBeNull();
});

test('a missing required fire field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $state = State::factory()->create();

    $attributes = PolicyPayload::fire($client, $carrier, $state, ['fire' => ['street' => null]]);

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyFireAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
