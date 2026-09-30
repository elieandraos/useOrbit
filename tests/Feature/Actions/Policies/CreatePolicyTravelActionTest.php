<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyTravelAction;
use App\Enums\TravelCoverageTier;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('a travel policy stores its trip detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyTravelAction::class)->handle($user, PolicyPayload::travel($client, $carrier, [
        'travel' => [
            'destination' => 'Portugal',
            'trip_start_date' => '2026-06-01',
            'trip_end_date' => '2026-06-15',
            'travelers' => 'Jane Doe, John Doe',
            'coverage_tier' => 'standard',
        ],
    ]));

    expect($policy->travelDetails->destination)->toBe('Portugal')
        ->and($policy->travelDetails->trip_start_date->format('Y-m-d'))->toBe('2026-06-01')
        ->and($policy->travelDetails->trip_end_date->format('Y-m-d'))->toBe('2026-06-15')
        ->and($policy->travelDetails->travelers)->toBe('Jane Doe, John Doe')
        ->and($policy->travelDetails->coverage_tier)->toBe(TravelCoverageTier::Standard);
});

test('a travel policy does not populate policy_insureds', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyTravelAction::class)->handle($user, PolicyPayload::travel($client, $carrier));

    expect($policy->insureds()->count())->toBe(0);
});

test('a missing required trip field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::travel($client, $carrier, ['travel' => ['destination' => null]]);

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyTravelAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
