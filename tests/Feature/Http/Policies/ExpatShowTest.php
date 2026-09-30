<?php

declare(strict_types=1);

use App\Http\Resources\PolicyExpatResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->expat()->create();

    $this->get(route('policies.expat.show', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a policy from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertOk();

    $policy->load(['client', 'carrier', 'agent', 'expatDetails.country']);

    $response->assertHasResource('policy', PolicyExpatResource::make($policy));
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->expat()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-expat policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertNotFound();
});

test('the policy exposes its assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Khoury']);
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.agent.slug', $agent->slug)
            ->where('policy.agent.full_name', 'Nadia Khoury')
        );
});

test('a policy with no assigned agent exposes a null agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('an expat policy exposes its detail fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);
    $policy->expatDetails->update([
        'coverage_zone' => 'in_out',
        'travel_scope' => 'Worldwide',
        'full_name' => 'Sami Nakhle',
        'gender' => 'male',
        'nationality' => 'Lebanese',
        'date_of_birth' => '1980-05-15',
        'phone' => '+96170111222',
        'visa_expiry_date' => '2027-01-01',
    ]);

    $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.coverage_zone', 'in_out')
            ->where('policy.details.coverage_zone_label', 'In-Out')
            ->where('policy.details.travel_scope', 'Worldwide')
            ->where('policy.details.full_name', 'Sami Nakhle')
            ->where('policy.details.gender_label', 'Male')
            ->where('policy.details.nationality', 'Lebanese')
            ->where('policy.details.date_of_birth', '1980-05-15')
            ->where('policy.details.phone', '+96170111222')
            ->where('policy.details.visa_expiry_date', '2027-01-01')
        );
});

test('no insureds key is present on an expat policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->expat()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.expat.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('policy.insureds'));
});
