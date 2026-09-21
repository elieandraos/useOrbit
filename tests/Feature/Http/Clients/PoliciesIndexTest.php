<?php

declare(strict_types=1);

use App\Http\Resources\ClientResource;
use App\Http\Resources\PolicyResource;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.policies.index', $client))
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('clients.policies.index', $client))
        ->assertNotFound();
});

test('authenticated user can list a client policies', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id, 'client_id' => $client->id]);

    $this->actingAs($user)
        ->get(route('clients.policies.index', $client))
        ->assertOk()
        ->assertHasResource('client', ClientResource::make($client))
        ->assertHasPaginatedResource(
            'policies',
            PolicyResource::collection(
                $client->policies()->with(['client', 'carrier'])->latest('effective_date')->orderBy('id')->paginate(7)
            )
        );
});

test('policies from another client are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);
    $otherClient = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id, 'client_id' => $client->id]);
    Policy::factory(3)->forOrganization($user)->create(['created_by' => $user->id, 'client_id' => $otherClient->id]);

    $this->actingAs($user)
        ->get(route('clients.policies.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('policies.data', 2));
});

test('the endpoint returns a valid paginated response when the client has zero policies', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('clients.policies.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policies.data', 0)
            ->has('policies.meta')
            ->has('policies.links')
        );
});
