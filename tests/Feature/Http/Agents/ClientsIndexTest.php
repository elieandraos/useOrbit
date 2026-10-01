<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.clients.index', $agent))
        ->assertRedirect(route('login'));
});

test('authenticated user can list an agent\'s clients', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    Policy::factory(2)->forOrganization($user)->create([
        'agent_id' => $agent->id,
        'client_id' => fn () => Client::factory()->forOrganization($user)->create()->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('agents.clients.index', $agent))
        ->assertOk()
        ->assertHasResource('agent', AgentResource::make($agent))
        ->assertInertia(fn ($page) => $page
            ->component('AgentClients/Index')
            ->where('policiesCount', 2)
            ->has('clients.data', 2)
        );
});

test('lists each of the agent\'s distinct, non-deleted clients once across live policies of any status', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    $repeatClient = Client::factory()->forOrganization($user)->create();
    $archivedClient = Client::factory()->forOrganization($user)->archived()->create();
    $deletedClient = Client::factory()->forOrganization($user)->create();
    $clientWithDeletedPolicy = Client::factory()->forOrganization($user)->create();
    $otherAgentClient = Client::factory()->forOrganization($user)->create();

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id, 'status' => PolicyStatus::Cancelled->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $archivedClient->id, 'status' => PolicyStatus::Frozen->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $deletedClient->id]);
    $deletedClient->delete();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $clientWithDeletedPolicy->id])->delete();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => Agent::factory()->forOrganization($user), 'client_id' => $otherAgentClient->id]);

    $response = $this->actingAs($user)
        ->get(route('agents.clients.index', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('clients.meta.total', 2));

    expect(collect($response->inertiaProps('clients.data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$repeatClient->id, $archivedClient->id])->sort()->values()->all());
});

test('the clients total matches the agent\'s clients count on the show page', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    $repeatClient = Client::factory()->forOrganization($user)->create();
    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => $repeatClient->id]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'agent_id' => $agent->id, 'client_id' => Client::factory()->forOrganization($user)]);

    $this->actingAs($user)
        ->get(route('agents.show', $agent))
        ->assertInertia(fn ($page) => $page->where('clientsCount', 2));

    $this->actingAs($user)
        ->get(route('agents.clients.index', $agent))
        ->assertInertia(fn ($page) => $page->where('clients.meta.total', 2));
});

test('clients are ordered by name and paginated seven per page', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();
    $names = ['Hana', 'Bilal', 'Ghada', 'Adam', 'Fadi', 'Carla', 'Elie', 'Dina'];

    foreach ($names as $firstName) {
        Policy::factory()->forOrganization($user)->create([
            'created_by' => $user->id,
            'agent_id' => $agent->id,
            'client_id' => Client::factory()->forOrganization($user)->create(['first_name' => $firstName]),
        ]);
    }

    $this->actingAs($user)
        ->get(route('agents.clients.index', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.meta.total', 8)
            ->has('clients.data', 7)
            ->where('clients.data.0.first_name', 'Adam')
            ->where('clients.data.6.first_name', 'Ghada')
        );

    $this->actingAs($user)
        ->get(route('agents.clients.index', ['agent' => $agent, 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.first_name', 'Hana')
        );
});

test('an agent with no clients gets an empty list', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('agents.clients.index', $agent))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 0)
            ->where('policiesCount', 0)
        );
});

test('authenticated user gets 404 for an agent from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $agent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('agents.clients.index', $agent))
        ->assertNotFound();
});
