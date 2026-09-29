<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyLifeAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('updates the policy_life_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $detailsId = $policy->lifeDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyLifeAction::class)->handle($user, $policy, PolicyPayload::life($client, $carrier, [
        'life' => [
            'sum_assured' => '200000.00',
            'term_years' => 15,
            'smoker' => true,
            'beneficiaries' => 'John Smith (100%)',
        ],
    ]));

    $fresh = $policy->fresh('lifeDetails');
    expect($fresh->lifeDetails->id)->toBe($detailsId)
        ->and($fresh->lifeDetails->sum_assured)->toBe('200000.00')
        ->and($fresh->lifeDetails->term_years)->toBe(15)
        ->and($fresh->lifeDetails->smoker)->toBeTrue()
        ->and($fresh->lifeDetails->beneficiaries)->toBe('John Smith (100%)');
});

test('an invalid life field leaves the policy and its details unchanged', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->life()->create([
        'created_by' => $user->id,
        'premium_amount' => '500.00',
    ]);

    $attributes = PolicyPayload::life($client, $carrier, ['life' => ['sum_assured' => null]]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyLifeAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
