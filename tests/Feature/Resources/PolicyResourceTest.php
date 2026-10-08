<?php

declare(strict_types=1);

use App\Enums\PolicyDisplayStatus;
use App\Enums\PolicyStatus;
use App\Http\Resources\PolicyAutomotiveResource;
use App\Http\Resources\PolicyExpatResource;
use App\Http\Resources\PolicyFireResource;
use App\Http\Resources\PolicyLifeResource;
use App\Http\Resources\PolicyMedicalResource;
use App\Http\Resources\PolicyResource;
use App\Http\Resources\PolicyTravelResource;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

dataset('policy class resources', [
    'automotive' => ['automotive', PolicyAutomotiveResource::class, 'automotiveDetails'],
    'expat' => ['expat', PolicyExpatResource::class, 'expatDetails'],
    'fire' => ['fire', PolicyFireResource::class, 'fireDetails'],
    'life' => ['life', PolicyLifeResource::class, 'lifeDetails'],
    'medical' => ['medical', PolicyMedicalResource::class, 'medicalDetails'],
    'travel' => ['travel', PolicyTravelResource::class, 'travelDetails'],
]);

test('a class resource exposes the base policy representation plus its own details', function (string $class, string $resource, string $detailsRelation) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->{$class}()->create(['created_by' => $user->id]);
    PolicyInsured::factory()->for($policy)->create();
    $policy->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency', $detailsRelation, 'insureds']);

    $base = Arr::except(PolicyResource::make($policy)->resolve(), 'insureds');
    $serialized = $resource::make($policy)->resolve();

    expect(Arr::except($serialized, ['details', 'insureds']))->toBe($base)
        ->and($serialized)->toHaveKey('details')
        ->and(array_key_exists('insureds', $serialized))->toBe($resource === PolicyMedicalResource::class);
})->with('policy class resources');

test('a class resource omits relations that were not loaded', function (string $class, string $resource) {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->{$class}()->create(['created_by' => $user->id]);

    expect($resource::make($policy->fresh())->resolve())
        ->toHaveKeys(['id', 'policy_number', 'net_premium', 'display_status_label'])
        ->not->toHaveKeys(['client', 'carrier', 'carrier_branch', 'agent', 'currency_code', 'details', 'insureds']);
})->with('policy class resources');

test('the resource exposes the code of the policy currency once it is loaded', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->lbp()->create(['created_by' => $user->id]);

    expect(PolicyResource::make($policy->load('currency'))->resolve())
        ->toHaveKey('currency_code', 'LBP');
});

test('the resource exposes the display status in place of the stored status', function () {
    $this->travelTo('2026-03-10 12:00:00');
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    $policy = Policy::factory()->forOrganization($user)->create([
        'created_by' => $user->id,
        'status' => PolicyStatus::Active,
        'effective_date' => '2026-03-11',
        'expiry_date' => '2027-03-10',
    ]);

    expect(PolicyResource::make($policy->fresh())->resolve())
        ->toMatchArray([
            'display_status' => PolicyDisplayStatus::Upcoming,
            'display_status_label' => 'Upcoming',
        ])
        ->not->toHaveKeys(['status', 'status_label']);
});

test('a policy collection resolves the organization date once, not once per row', function () {
    $user = User::factory()->withOrganization()->create();
    setOrganizationContext($user);
    Policy::factory(3)->forOrganization($user)->create(['created_by' => $user->id]);
    $policies = Policy::query()->get();

    DB::enableQueryLog();
    PolicyResource::collection($policies)->resolve();

    expect(DB::getQueryLog())->toHaveCount(1);
});
