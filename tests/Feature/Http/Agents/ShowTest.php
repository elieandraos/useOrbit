<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.show', $agent))
        ->assertRedirect(route('login'));
});

test('authenticated user can view an agent from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertHasResource('agent', AgentResource::make($agent->load(['country', 'state'])));
});

test('authenticated user gets 404 for an agent from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $agent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertNotFound();
});

test('an archived agent can still be shown', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk();
});

test('policiesCount reflects only the policies written through this agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->count(2)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
    ]);

    $otherAgent = Agent::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $otherAgent->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policiesCount', 2));
});

test('renewingPolicies lists only this agent\'s active policies expiring within the renewal window', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $renewing = Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);

    // Outside the renewal window.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(60)->toDateString(),
    ]);

    // Already expired.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->subDays(2)->toDateString(),
    ]);

    // Within the window but cancelled.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Cancelled->value,
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    // Within the window but written through another agent.
    $otherAgent = Agent::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $otherAgent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('renewingPolicies', 1)
            ->where('renewingPolicies.0.id', $renewing->id)
        );
});

test('renewingPolicies includes both edges of the renewal window', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $expiringToday = Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->toDateString(),
    ]);
    $expiringAtWindowEdge = Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);
    // One day past the window.
    Policy::factory()->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
        'status' => PolicyStatus::Active->value,
        'expiry_date' => now()->addDays(31)->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('renewingPolicies', 2)
            ->where('renewingPolicies.0.id', $expiringToday->id)
            ->where('renewingPolicies.1.id', $expiringAtWindowEdge->id)
        );
});
