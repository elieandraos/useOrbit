<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('guests are redirected to the login page', function () {
    $this->get(route('clients.search', ['search' => 'Maya']))
        ->assertRedirect(route('login'));
});

test('the client search is authorized through the client view-any ability', function () {
    $user = User::factory()->withOrganization()->create();

    Gate::before(fn (User $user, string $ability, array $arguments): ?bool => $ability === 'viewAny' && ($arguments[0] ?? null) === Client::class ? false : null);

    $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => 'Maya']))
        ->assertForbidden();
});

test('searches with fewer than 2 characters are rejected', function (?string $search) {
    $user = User::factory()->withOrganization()->create();
    Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya']);

    $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => $search]))
        ->assertRedirect()
        ->assertSessionHasErrors('search');
})->with([
    'missing' => null,
    'one character' => 'M',
]);

test('the search matches a client by each of its searchable fields', function (array $attributes, string $search) {
    $user = User::factory()->withOrganization()->create();
    $match = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, ...$attributes]);
    Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Karim', 'last_name' => 'Nassar']);

    $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => $search]))
        ->assertOk()
        ->assertExactJson(['data' => [['id' => $match->id, 'full_name' => $match->full_name]]]);
})->with([
    'first name' => [['first_name' => 'Zephyrine'], 'zephyr'],
    'middle name' => [['middle_name' => 'Quillon'], 'quill'],
    'last name' => [['last_name' => 'Vartanian'], 'artani'],
    'company name' => [['client_type' => 'company', 'company_name' => 'Bristol Trading'], 'bristol'],
    'phone' => [['phone' => '+961 71 424 242'], '424 242'],
    'email' => [['email' => 'zephyr.client@example.test'], 'zephyr.client@'],
]);

test('the search returns only the current organization\'s clients', function () {
    $user = User::factory()->withOrganization()->create();
    $ownClient = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya']);
    Client::factory()->for(Organization::factory())->create(['first_name' => 'Maya']);

    $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => 'Maya']))
        ->assertOk()
        ->assertExactJson(['data' => [['id' => $ownClient->id, 'full_name' => $ownClient->full_name]]]);
});

test('the search returns only active clients', function () {
    $user = User::factory()->withOrganization()->create();
    $activeClient = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya']);
    Client::factory()->forOrganization($user)->archived()->create(['created_by' => $user->id, 'first_name' => 'Maya']);

    $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => 'Maya']))
        ->assertOk()
        ->assertExactJson(['data' => [['id' => $activeClient->id, 'full_name' => $activeClient->full_name]]]);
});

test('the search orders clients by their displayed name', function () {
    $user = User::factory()->withOrganization()->create();
    $zoeKhoury = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Zoe', 'last_name' => 'Khoury', 'email' => 'zoe@orbit.test']);
    $bristolTrading = Client::factory()->forOrganization($user)->company()->create(['created_by' => $user->id, 'company_name' => 'Bristol Trading', 'first_name' => 'Zack', 'last_name' => 'Zein', 'email' => 'zack@orbit.test']);
    $mayaAbboud = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya', 'last_name' => 'Abboud', 'email' => 'maya.a@orbit.test']);
    $mayaHaddad = Client::factory()->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya', 'last_name' => 'Haddad', 'email' => 'maya.h@orbit.test']);

    $response = $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => 'orbit.test']))
        ->assertOk();

    expect($response->json('data.*.id'))->toBe([$bristolTrading->id, $mayaAbboud->id, $mayaHaddad->id, $zoeKhoury->id]);
});

test('the search returns at most 10 clients', function () {
    $user = User::factory()->withOrganization()->create();
    Client::factory(11)->forOrganization($user)->create(['created_by' => $user->id, 'first_name' => 'Maya']);

    $this->actingAs($user)
        ->getJson(route('clients.search', ['search' => 'Maya']))
        ->assertOk()
        ->assertJsonCount(10, 'data');
});
