<?php

declare(strict_types=1);

use App\Http\Resources\PolicyFireResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->fire()->create();

    $this->get(route('policies.fire.show', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a policy from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertOk();

    $policy->load(['client', 'carrier', 'agent', 'fireDetails.state', 'fireDetails.country']);

    $response->assertHasResource('policy', PolicyFireResource::make($policy));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->fire()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertNotFound();
});

test('the policy exposes its assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Khoury']);
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.agent.slug', $agent->slug)
            ->where('policy.agent.full_name', 'Nadia Khoury')
        );
});

test('a policy with no assigned agent exposes a null agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('a fire policy exposes its property detail fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);
    $policy->fireDetails->update([
        'property_type' => 'Commercial building',
        'floor_area' => 320,
        'year_built' => 2010,
        'street' => 'Verdun Street',
        'building_floor' => 'Floor 5',
        'city' => 'Beirut',
        'sum_insured' => '750000.00',
    ]);

    $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.property_type', 'Commercial building')
            ->where('policy.details.floor_area', 320)
            ->where('policy.details.year_built', 2010)
            ->where('policy.details.street', 'Verdun Street')
            ->where('policy.details.building_floor', 'Floor 5')
            ->where('policy.details.city', 'Beirut')
            ->where('policy.details.sum_insured', '750000.00')
        );
});

test('no insureds key is present on a fire policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->fire()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.fire.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('policy.insureds'));
});
