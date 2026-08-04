<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->delete(route('agents.destroy', $agent))
        ->assertRedirect(route('login'));
});

test('owner can soft delete an agent from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $agent = Agent::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)
        ->delete(route('agents.destroy', $agent))
        ->assertRedirect(route('agents.index'))
        ->assertHasInertiaFlash('success', 'Agent deleted.');

    $this->assertSoftDeleted($agent);
});

test('non-owner member is forbidden from deleting an agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->delete(route('agents.destroy', $agent))
        ->assertForbidden();
});
