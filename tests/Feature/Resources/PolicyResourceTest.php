<?php

declare(strict_types=1);

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
    $policy->load(['client', 'carrier', 'agent', $detailsRelation, 'insureds']);

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
        ->toHaveKeys(['id', 'policy_number', 'net_premium', 'status_label'])
        ->not->toHaveKeys(['client', 'carrier', 'agent', 'details', 'insureds']);
})->with('policy class resources');
