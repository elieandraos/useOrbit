<?php

declare(strict_types=1);

use App\Http\Resources\PolicyLifeResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->life()->create();

    $this->get(route('policies.life.show', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a policy from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertOk();

    $policy->load(['client', 'carrier', 'agent', 'lifeDetails']);

    $response->assertHasResource('policy', PolicyLifeResource::make($policy));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->life()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-life policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertNotFound();
});

test('the policy exposes its assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Khoury']);
    $policy = Policy::factory()->forOrganization($user)->life()->create([
        'created_by' => $user->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.agent.slug', $agent->slug)
            ->where('policy.agent.full_name', 'Nadia Khoury')
        );
});

test('a policy with no assigned agent exposes a null agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('a life policy exposes its life detail fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);
    $policy->lifeDetails->update([
        'sum_assured' => '250000.00',
        'term_years' => 25,
        'smoker' => true,
        'beneficiaries' => 'Maya Haddad (100%)',
    ]);

    $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.sum_assured', '250000.00')
            ->where('policy.details.term_years', 25)
            ->where('policy.details.smoker', true)
            ->where('policy.details.beneficiaries', 'Maya Haddad (100%)')
        );
});

test('no insureds key is present on a life policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->life()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.life.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('policy.insureds'));
});
