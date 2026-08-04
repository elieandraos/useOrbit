<?php

declare(strict_types=1);

use App\Enums\AgentStatus;
use App\Enums\OrganizationRole;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->patch(route('agents.archive', $agent))
        ->assertRedirect(route('login'));
});

test('owner can archive an agent from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)
        ->patch(route('agents.archive', $agent))
        ->assertRedirect(route('agents.index'))
        ->assertHasInertiaFlash('success', 'Agent archived.');

    /** @var Agent $fresh */
    $fresh = $agent->fresh();
    expect($fresh->status)->toBe(AgentStatus::Archived);

    $this->assertNotSoftDeleted($agent);
});

test('non-owner member is forbidden from archiving an agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('agents.archive', $agent))
        ->assertForbidden();
});
