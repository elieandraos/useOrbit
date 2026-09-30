<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyLifeAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('creating a life policy stores a life detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyLifeAction::class)->handle($user, PolicyPayload::life($client, $carrier, [
        'life' => [
            'sum_assured' => '150000.00',
            'term_years' => 20,
            'smoker' => false,
            'beneficiaries' => 'Jane Doe (100%)',
        ],
    ]));

    expect($policy->lifeDetails->sum_assured)->toBe('150000.00')
        ->and($policy->lifeDetails->term_years)->toBe(20)
        ->and($policy->lifeDetails->smoker)->toBeFalse()
        ->and($policy->lifeDetails->beneficiaries)->toBe('Jane Doe (100%)');
});

test('a smoker flag is stored as a boolean', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::life($client, $carrier, ['life' => ['smoker' => true]]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyLifeAction::class)->handle($user, $attributes);

    expect($policy->lifeDetails->smoker)->toBeTrue();
});

test('a missing required life field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::life($client, $carrier, ['life' => ['sum_assured' => null]]);

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyLifeAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
