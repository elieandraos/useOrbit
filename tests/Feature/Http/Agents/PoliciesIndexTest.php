<?php

declare(strict_types=1);

use App\Http\Resources\AgentResource;
use App\Http\Resources\PolicyResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.policies.index', $agent))
        ->assertRedirect(route('login'));
});

test('authenticated user can list an agent\'s policies', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    Policy::factory(2)->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('agents.policies.index', $agent))
        ->assertOk()
        ->assertHasResource('agent', AgentResource::make($agent))
        ->assertInertia(fn ($page) => $page->where('policiesCount', 2))
        ->assertHasPaginatedResource(
            'policies',
            PolicyResource::collection(
                $agent->policies()->with(['client', 'carrier'])->latest('effective_date')->orderBy('id')->paginate(7)
            )
        );
});

test('policies written through another agent are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    $otherAgent = Agent::factory()->forOrganization($user)->create();

    Policy::factory(2)->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'created_by' => $user->id,
    ]);
    Policy::factory(3)->forOrganization($user)->create([
        'agent_id' => $otherAgent->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('agents.policies.index', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('policies.data', 2));
});

test('authenticated user gets 404 for an agent from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $agent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('agents.policies.index', $agent))
        ->assertNotFound();
});
