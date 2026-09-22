<?php

declare(strict_types=1);

use App\Enums\PolicyType;
use App\Http\Resources\PolicyInsuredResource;
use App\Http\Resources\PolicyMedicalResource;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create(['type' => PolicyType::Group]);

    $this->get(route('policies.members.index', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can list a group policy members', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group,
    ]);
    PolicyInsured::factory(2)->for($policy)->create();

    $this->actingAs($user)
        ->get(route('policies.members.index', $policy))
        ->assertOk()
        ->assertHasResource('policy', PolicyMedicalResource::make($policy->load(['client', 'carrier', 'agent'])))
        ->assertHasResource(
            'members',
            PolicyInsuredResource::collection($policy->insureds()->orderBy('full_name')->get())
        );
});

test('members expose a computed age alongside their other fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group,
    ]);
    PolicyInsured::factory()->for($policy)->create([
        'full_name' => 'Marc Aoun',
        'date_of_birth' => now()->subYears(30)->subDays(1)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('policies.members.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('members.0.full_name', 'Marc Aoun')
            ->where('members.0.age', 30)
        );
});

test('members from another policy are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group,
    ]);
    $otherPolicy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group,
    ]);

    PolicyInsured::factory(2)->for($policy)->create();
    PolicyInsured::factory(3)->for($otherPolicy)->create();

    $this->actingAs($user)
        ->get(route('policies.members.index', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('members', 2));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create([
        'organization_id' => $otherOrganization->id,
        'type' => PolicyType::Group,
    ]);

    $this->actingAs($user)
        ->get(route('policies.members.index', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-medical policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group,
    ]);

    $this->actingAs($user)
        ->get(route('policies.members.index', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a single-type policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Single,
    ]);

    $this->actingAs($user)
        ->get(route('policies.members.index', $policy))
        ->assertNotFound();
});
