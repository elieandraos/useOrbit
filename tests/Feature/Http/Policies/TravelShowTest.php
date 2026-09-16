<?php

declare(strict_types=1);

use App\Http\Resources\PolicyTravelResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->travel()->create();

    $this->get(route('policies.travel.show', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a policy from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertOk();

    $policy->load(['client', 'carrier', 'agent', 'travelDetails']);

    $response->assertHasResource('policy', PolicyTravelResource::make($policy));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->travel()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-travel policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertNotFound();
});

test('the policy exposes its assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Khoury']);
    $policy = Policy::factory()->forOrganization($user)->travel()->create([
        'created_by' => $user->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.agent.slug', $agent->slug)
            ->where('policy.agent.full_name', 'Nadia Khoury')
        );
});

test('a policy with no assigned agent exposes a null agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('a travel policy exposes its trip detail fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);
    $policy->travelDetails->update([
        'destination' => 'Italy',
        'trip_start_date' => '2026-08-01',
        'trip_end_date' => '2026-08-14',
        'travelers' => 'Sami Karam',
        'coverage_tier' => 'Basic',
    ]);

    $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.destination', 'Italy')
            ->where('policy.details.trip_start_date', '2026-08-01')
            ->where('policy.details.trip_end_date', '2026-08-14')
            ->where('policy.details.travelers', 'Sami Karam')
            ->where('policy.details.coverage_tier', 'Basic')
        );
});

test('no insureds key is present on a travel policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->travel()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.travel.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('policy.insureds'));
});
