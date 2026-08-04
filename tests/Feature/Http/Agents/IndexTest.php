<?php

declare(strict_types=1);

use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('agents.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can list their organization agents', function () {
    $user = User::factory()->withOrganization()->create();
    Agent::factory(2)->forOrganization($user)->create();

    $this->assertDatabaseCount('agents', 2);

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'agents',
            AgentResource::collection(Agent::query()->orderBy('last_name')->orderBy('first_name')->paginate(7))
        );
});

test('agents are ordered by last name then first name', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Agent $charlie */
    $charlie = Agent::factory()->forOrganization($user)->create(['first_name' => 'Amy', 'last_name' => 'Charlie']);
    /** @var Agent $alpha */
    $alpha = Agent::factory()->forOrganization($user)->create(['first_name' => 'Zoe', 'last_name' => 'Alpha']);
    /** @var Agent $bravo */
    $bravo = Agent::factory()->forOrganization($user)->create(['first_name' => 'Mona', 'last_name' => 'Bravo']);

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agents.data.0.id', $alpha->id)
            ->where('agents.data.1.id', $bravo->id)
            ->where('agents.data.2.id', $charlie->id)
        );
});

test('a sort query param reorders the agents and is echoed back to the page', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Agent $bravo */
    $bravo = Agent::factory()->forOrganization($user)->create(['last_name' => 'Bravo']);
    /** @var Agent $alpha */
    $alpha = Agent::factory()->forOrganization($user)->create(['last_name' => 'Alpha']);

    $this->actingAs($user)
        ->get(route('agents.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agents.data.0.id', $bravo->id)
            ->where('agents.data.1.id', $alpha->id)
            ->where('sort.column', 'name')
            ->where('sort.direction', 'desc')
        );
});

test('the index page echoes the default sort when none is applied', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', 'name')
            ->where('sort.direction', 'asc')
        );
});

test('agents from another organization are not included', function () {
    $user = User::factory()->withOrganization()->create();
    Agent::factory(2)->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Agent::factory(3)->for($otherOrganization)->create();

    $this->assertDatabaseCount('agents', 5);

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'agents',
            AgentResource::collection(
                Agent::query()->where('organization_id', $user->current_organization_id)->orderBy('last_name')->orderBy('first_name')->paginate(7)
            )
        );
});
