<?php

declare(strict_types=1);

use App\Actions\Policies\CreatePolicyExpatAction;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\Support\PolicyPayload;

test('an in-zone policy stores an expat detail row with no travel scope', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyExpatAction::class)->handle($user, PolicyPayload::expat($client, $carrier, [
        'expat' => ['full_name' => 'Karim Saad'],
    ]));

    expect($policy->expatDetails->full_name)->toBe('Karim Saad')
        ->and($policy->expatDetails->coverage_zone->value)->toBe('in')
        ->and($policy->expatDetails->travel_scope)->toBeNull();
});

test('an in-out zone policy stores its travel scope and visa expiry', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $country = Country::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $policy = app(CreatePolicyExpatAction::class)->handle($user, PolicyPayload::expatInOut($client, $carrier, [
        'expat' => ['travel_scope' => 'Worldwide', 'country_id' => $country->id, 'visa_expiry_date' => '2027-06-01'],
    ]));

    expect($policy->expatDetails->travel_scope)->toBe('Worldwide')
        ->and($policy->expatDetails->country_id)->toBe($country->id)
        ->and($policy->expatDetails->visa_expiry_date->format('Y-m-d'))->toBe('2027-06-01');
});

test('a missing required expat field leaves no partial policy or detail row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $attributes = PolicyPayload::expat($client, $carrier, ['expat' => ['full_name' => null]]);

    $attempt = function () use ($user, $attributes): Policy {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(CreatePolicyExpatAction::class)->handle($user, $attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Policy::query()->count())->toBe(0);
});
