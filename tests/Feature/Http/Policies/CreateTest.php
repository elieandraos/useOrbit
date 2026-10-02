<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('policies.create'))
        ->assertRedirect(route('login'));
});

test('nothing is preselected by default', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selected', [
            'class' => null,
            'type' => null,
            'client_id' => null,
            'carrier_id' => null,
            'agent_id' => null,
            'status' => null,
            'source' => null,
        ]));
});

test('the carried-over first-step values are restored', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('policies.create', [
            'class' => 'life',
            'type' => 'group',
            'client_id' => $client->id,
            'carrier_id' => $carrier->id,
            'agent_id' => $agent->id,
            'status' => 'frozen',
            'source' => 'friend',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selected', [
            'class' => 'life',
            'type' => 'group',
            'client_id' => $client->id,
            'carrier_id' => $carrier->id,
            'agent_id' => $agent->id,
            'status' => 'frozen',
            'source' => 'friend',
        ]));
});

test('parties from another organization are not preselected', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $otherClient = Client::factory()->for($otherOrganization)->create();
    $otherCarrier = Carrier::factory()->for($otherOrganization)->create();
    $otherAgent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('policies.create', [
            'client_id' => $otherClient->id,
            'carrier_id' => $otherCarrier->id,
            'agent_id' => $otherAgent->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.client_id', null)
            ->where('selected.carrier_id', null)
            ->where('selected.agent_id', null)
        );
});

test('unknown class, type, status, and source values are not preselected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.create', [
            'class' => 'marine',
            'type' => 'family',
            'status' => 'expired',
            'source' => 'billboard',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.class', null)
            ->where('selected.type', null)
            ->where('selected.status', null)
            ->where('selected.source', null)
        );
});

test('array class, type, status, and source values are not preselected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.create', [
            'class' => ['life'],
            'type' => ['group'],
            'status' => ['frozen'],
            'source' => ['friend'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.class', null)
            ->where('selected.type', null)
            ->where('selected.status', null)
            ->where('selected.source', null)
        );
});

test('the create page receives the shared form options with the policy classes and the selections', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Policies/Create')
            ->hasAll(['clients', 'carriers', 'agents', 'types', 'statuses', 'sources', 'classes', 'selected'])
        );
});

test('the create page orders clients, carriers and agents by their displayed name', function () {
    $user = User::factory()->withOrganization()->create();
    $zoeKhoury = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Zoe', 'last_name' => 'Khoury']);
    $bristolTrading = Client::factory()->forOrganization($user)->company()->create(['created_by' => $user->id, 'company_name' => 'Bristol Trading', 'first_name' => 'Zack', 'last_name' => 'Zein']);
    $mayaAbboud = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya', 'last_name' => 'Abboud']);
    $mayaHaddad = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya', 'last_name' => 'Haddad']);
    $zenith = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Zenith Insurance']);
    $allied = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id, 'name' => 'Allied Insurance']);
    $zoeAgent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Zoe', 'last_name' => 'Abboud']);
    $adamAgent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Adam', 'last_name' => 'Zein']);

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.0.id', $bristolTrading->id)
            ->where('clients.1.id', $mayaAbboud->id)
            ->where('clients.2.id', $mayaHaddad->id)
            ->where('clients.3.id', $zoeKhoury->id)
            ->where('carriers.0.id', $allied->id)
            ->where('carriers.1.id', $zenith->id)
            ->where('agents.0.id', $adamAgent->id)
            ->where('agents.1.id', $zoeAgent->id)
        );
});

test('the create page offers only active clients, carriers and agents', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->create();
    Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('policies.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients', 1)
            ->has('clients.0', fn ($option) => $option->where('id', $client->id)->where('full_name', $client->full_name))
            ->has('carriers', 1)
            ->has('carriers.0', fn ($option) => $option->where('id', $carrier->id)->where('name', $carrier->name))
            ->has('agents', 1)
            ->has('agents.0', fn ($option) => $option->where('id', $agent->id)->where('full_name', $agent->full_name))
        );
});

test('archived parties are not preselected', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    $carrier = Carrier::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id]);
    $agent = Agent::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('policies.create', [
            'client_id' => $client->id,
            'carrier_id' => $carrier->id,
            'agent_id' => $agent->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected.client_id', null)
            ->where('selected.carrier_id', null)
            ->where('selected.agent_id', null)
        );
});
