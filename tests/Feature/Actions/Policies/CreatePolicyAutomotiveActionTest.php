<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyAutomotiveAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('a third party policy stores a vehicle detail row with no valuation', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyAutomotiveAction::class)->handle($user, PolicyPayload::automotive($client, $carrier, [
        'automotive' => ['plate_number' => '123 AB', 'make' => 'Toyota'],
    ]));

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
    $policy = app(CreatePolicyAutomotiveAction::class)->handle($user, PolicyPayload::automotiveAllRisk($client, $carrier, [
        'automotive' => ['valuation_amount' => '65000.00', 'valuation_source' => 'Carrier assessor'],
    ]));

    expect($policy->automotiveDetails->valuation_amount)->toBe('65000.00')
        ->and($policy->automotiveDetails->valuation_source)->toBe('Carrier assessor');
});

test('a missing required vehicle field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::automotive($client, $carrier, ['automotive' => ['plate_number' => null]]);

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyAutomotiveAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
