<?php

declare(strict_types=1);

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'first_name' => 'Nadia',
    'last_name' => 'Fares',
    'date_of_birth' => '1988-09-02',
    'joined_at' => '2019-03-15',
    'phone' => '+961 3 555 555',
    'email' => 'nadia.fares@useorbit.com',
    'street' => 'Hamra Street',
    'building_floor' => 'Block 12',
    'city' => 'Beirut',
];

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.edit', $agent))->assertRedirect(route('login'));
    $this->patch(route('agents.update', $agent))->assertRedirect(route('login'));
});

test('edit page renders with agent data', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('agents.edit', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Agents/Edit')
            ->hasResource('agent', AgentResource::make($agent->load(['updatedBy', 'country', 'state'])))
        );
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('agents.update', $agent))
        ->assertSessionHasErrors(['first_name', 'last_name', 'date_of_birth', 'joined_at', 'phone', 'email']);
});

test('update redirects to agents.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('agents.update', $agent), $validPayload)
        ->assertRedirect(route('agents.show', $agent->fresh()))
        ->assertHasInertiaFlash('success', 'Agent updated.');

    expect($agent->fresh()->first_name)->toBe('Nadia');
});

test('user gets 404 when updating an agent from another organization', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $agent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->patch(route('agents.update', $agent), $validPayload)
        ->assertNotFound();
});
