<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
            AgentResource::collection(Agent::query()->withCount('policies')->withClientsCount()->orderBy('last_name')->orderBy('first_name')->orderBy('id')->paginate(7))
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

test('an invalid sort column is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('agents.index', ['sort' => 'phone']))
        ->assertInvalid(['sort']);
});

test('an invalid sort direction is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('agents.index', ['direction' => 'sideways']))
        ->assertInvalid(['direction']);
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

test('a search query param narrows the agents and is echoed back to the page', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Agent $match */
    $match = Agent::factory()->forOrganization($user)->create(['first_name' => 'Mira', 'last_name' => 'Olsen']);
    Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Fares']);

    $this->actingAs($user)
        ->get(route('agents.index', ['search' => 'Mira']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('agents.data', 1)
            ->where('agents.data.0.id', $match->id)
            ->where('filters.search', 'Mira')
        );
});

test('the index only shows active agents by default', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Agent $active */
    $active = Agent::factory()->forOrganization($user)->create();
    Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('agents.data', 1)
            ->where('agents.data.0.id', $active->id)
            ->where('filters.archived', false)
        );
});

test('an archived query param shows only archived agents', function () {
    $user = User::factory()->withOrganization()->create();

    Agent::factory()->forOrganization($user)->create();
    /** @var Agent $archived */
    $archived = Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('agents.index', ['archived' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('agents.data', 1)
            ->where('agents.data.0.id', $archived->id)
            ->where('filters.archived', true)
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
                Agent::query()->withCount('policies')->withClientsCount()->where('organization_id', $user->organization_id)->orderBy('last_name')->orderBy('first_name')->orderBy('id')->paginate(7)
            )
        );
});

test('each agent row counts its live policies of any status and its distinct clients', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['last_name' => 'Aaron']);
    $repeatClient = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id, 'status' => PolicyStatus::Cancelled->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $otherClient->id, 'status' => PolicyStatus::Frozen->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => Client::factory()->forOrganization($user)])->delete();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => Agent::factory()->forOrganization($user)->create(['last_name' => 'Zulu']), 'client_id' => $repeatClient->id]);

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agents.data.0.id', $agent->id)
            ->where('agents.data.0.policies_count', 3)
            ->where('agents.data.0.clients_count', 2)
        );
});

test('a soft-deleted client is left out of the agent client count while its policies still count', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    $deletedClient = Client::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $deletedClient->id]);
    $deletedClient->delete();

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agents.data.0.policies_count', 1)
            ->where('agents.data.0.clients_count', 0)
        );
});

test('policies and clients from another organization are not counted on an agent row', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Policy::factory()->for($otherOrganization)->create([
        'agent_id' => $agent->id,
        'client_id' => Client::factory()->for($otherOrganization),
    ]);

    $this->actingAs($user)
        ->get(route('agents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('agents.data.0.policies_count', 0)
            ->where('agents.data.0.clients_count', 0)
        );
});

test('the agent index runs the same number of queries regardless of how many rows have counts', function () {
    $user = User::factory()->withOrganization()->create();
    $countQueries = function () use ($user): int {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($user)->get(route('agents.index'))->assertOk();

        return $queries;
    };

    $countQueries(); // warm up one-off, per-process queries

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => Agent::factory()->forOrganization($user), 'client_id' => Client::factory()->forOrganization($user)]);
    $queriesForOneRow = $countQueries();

    Policy::factory(4)->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => fn () => Agent::factory()->forOrganization($user)->create()->id, 'client_id' => fn () => Client::factory()->forOrganization($user)->create()->id]);
    $queriesForFiveRows = $countQueries();

    expect($queriesForFiveRows)->toBe($queriesForOneRow);
});
