<?php

declare(strict_types=1);

use App\Actions\Policies\UpdatePolicyExpatAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyExpatDetails;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('updates the policy_expat_details row in place', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $detailsId = $policy->expatDetails->id;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyExpatAction::class)->handle($user, $policy, PolicyPayload::expat($client, $carrier, [
        'expat' => ['coverage_zone' => 'in', 'full_name' => 'Rami Haddad'],
    ]));

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
    $policy = Policy::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'class' => 'expat',
        'subclass' => 'Worldwide',
    ]);
    PolicyExpatDetails::factory()->for($policy)->create([
        'coverage_zone' => 'in',
        'travel_scope' => null,
        'visa_expiry_date' => null,
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdatePolicyExpatAction::class)->handle($user, $policy, PolicyPayload::expatInOut($client, $carrier, [
        'expat' => ['travel_scope' => 'Regional', 'visa_expiry_date' => '2027-03-01'],
    ]));

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

    $attributes = PolicyPayload::expat($client, $carrier, ['expat' => ['full_name' => null]]);

    $attempt = function () use ($user, $policy, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(UpdatePolicyExpatAction::class)->handle($user, $policy, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and($policy->fresh()->premium_amount)->toBe('500.00');
});
