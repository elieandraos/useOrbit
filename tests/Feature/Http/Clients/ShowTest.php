<?php

declare(strict_types=1);

use App\Http\Resources\ClientResource;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.show', $client))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a client from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertHasResource('client', ClientResource::make($client->load(['country', 'state'])));
});

test('policiesCount reflects the client\'s actual policy count', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();

    Policy::factory(2)->forOrganization($user)->create(['created_by' => $user->id, 'client_id' => $client->id]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'client_id' => $otherClient->id]);

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policiesCount', 2));
});

test('policiesCount is zero for a client with no policies', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policiesCount', 0));
});

test('recentPolicies lists the client\'s 5 most recent policies, newest effective date first with ties by id', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $carrier = Carrier::factory()->forOrganization($user)->create();

    Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'effective_date' => '2026-01-01']);
    $tiedFirst = Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'effective_date' => '2026-03-01']);
    $tiedSecond = Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'effective_date' => '2026-03-01']);
    $newest = Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'carrier_id' => $carrier->id, 'effective_date' => '2026-06-01']);
    $second = Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'effective_date' => '2026-05-01']);
    $fifth = Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'effective_date' => '2026-02-01']);

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentPolicies', 5)
            ->where('recentPolicies.0.id', $newest->id)
            ->where('recentPolicies.1.id', $second->id)
            ->where('recentPolicies.2.id', $tiedFirst->id)
            ->where('recentPolicies.3.id', $tiedSecond->id)
            ->where('recentPolicies.4.id', $fifth->id)
            ->where('recentPolicies.0.carrier.name', $carrier->name)
        );
});

test('recentPolicies excludes other clients\' and other organizations\' policies', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();

    $ownPolicy = Policy::factory()->forOrganization($user)->create(['client_id' => $client->id, 'effective_date' => '2026-01-01']);
    Policy::factory()->forOrganization($user)->create(['client_id' => $otherClient->id, 'effective_date' => '2026-06-01']);
    Policy::factory()->for(Organization::factory())->create(['client_id' => $client->id, 'effective_date' => '2026-06-01']);

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentPolicies', 1)
            ->where('recentPolicies.0.id', $ownPolicy->id)
        );
});

test('recentPolicies is empty for a client with no policies', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('recentPolicies', 0));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertNotFound();
});

test('an archived client can still be shown', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk();
});

test('a company client can be shown without a date of birth or gender', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->company()->create();

    $this->actingAs($user)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertHasResource('client', ClientResource::make($client->load(['country', 'state'])));
});
