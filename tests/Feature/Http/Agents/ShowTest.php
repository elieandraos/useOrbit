<?php

declare(strict_types=1);

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Organization;
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
