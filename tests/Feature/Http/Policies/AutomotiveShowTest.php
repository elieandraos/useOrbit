<?php

declare(strict_types=1);

use App\Http\Resources\PolicyAutomotiveResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->automotive()->create();

    $this->get(route('policies.automotive.show', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a policy from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertOk();

    $policy->load(['client', 'carrier', 'agent', 'automotiveDetails']);

    $response->assertHasResource('policy', PolicyAutomotiveResource::make($policy));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->automotive()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-automotive policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertNotFound();
});

test('the policy exposes its assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Khoury']);
    $policy = Policy::factory()->forOrganization($user)->automotive()->create([
        'created_by' => $user->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.agent.slug', $agent->slug)
            ->where('policy.agent.full_name', 'Nadia Khoury')
        );
});

test('a policy with no assigned agent exposes a null agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('an automotive policy exposes its vehicle detail fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);
    $policy->automotiveDetails->update([
        'plate_number' => '999 ZZ',
        'make' => 'Audi',
        'model' => 'A4',
        'year' => 2020,
        'vin' => '1HGCM82633A123456',
        'color' => 'Blue',
        'valuation_amount' => '30000.00',
        'valuation_source' => 'Independent appraisal',
    ]);

    $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.plate_number', '999 ZZ')
            ->where('policy.details.make', 'Audi')
            ->where('policy.details.model', 'A4')
            ->where('policy.details.year', 2020)
            ->where('policy.details.vin', '1HGCM82633A123456')
            ->where('policy.details.color', 'Blue')
            ->where('policy.details.valuation_amount', '30000.00')
            ->where('policy.details.valuation_source', 'Independent appraisal')
        );
});

test('no insureds key is present on an automotive policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.automotive.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('policy.insureds'));
});
