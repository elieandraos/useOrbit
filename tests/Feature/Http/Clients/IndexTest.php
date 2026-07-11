<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('clients.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can list their organization clients', function () {
    $user = User::factory()->withOrganization()->create();
    Client::factory(2)->create(['organization_id' => $user->current_organization_id]);

    $this->assertDatabaseCount('clients', 2);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertHasPaginatedResource('clients', ClientResource::collection(Client::query()->latest('enrollment_date')->paginate(7)));
});

test('clients are ordered by enrollment date, newest first', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Client $oldest */
    $oldest = Client::factory()->create(['organization_id' => $user->current_organization_id, 'enrollment_date' => '2023-01-01']);

    /** @var Client $newest */
    $newest = Client::factory()->create(['organization_id' => $user->current_organization_id, 'enrollment_date' => '2024-06-01']);

    /** @var Client $middle */
    $middle = Client::factory()->create(['organization_id' => $user->current_organization_id, 'enrollment_date' => '2024-01-01']);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.data.0.id', $newest->id)
            ->where('clients.data.1.id', $middle->id)
            ->where('clients.data.2.id', $oldest->id)
        );
});

test('clients from another organization are not included', function () {
    $user = User::factory()->withOrganization()->create();
    Client::factory(2)->create(['organization_id' => $user->current_organization_id]);

    $otherOrganization = Organization::factory()->create();
    Client::factory(3)->create(['organization_id' => $otherOrganization->id]);

    $this->assertDatabaseCount('clients', 5);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'clients',
            ClientResource::collection(
                Client::query()->where('organization_id', $user->current_organization_id)->latest('enrollment_date')->paginate(7)
            )
        );
});

test('a filter query param narrows the response to matching clients', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Client $match */
    $match = Client::factory()->create(['organization_id' => $user->current_organization_id, 'first_name' => 'Aline']);
    Client::factory()->create(['organization_id' => $user->current_organization_id, 'first_name' => 'Karim']);

    $this->actingAs($user)
        ->get(route('clients.index', ['search' => 'Aline']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.id', $match->id)
        );
});

test('an invalid gender is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', ['gender' => 'other']))
        ->assertInvalid(['gender']);
});

test('enrolled_to before enrolled_from is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', ['enrolled_from' => '2024-01-10', 'enrolled_to' => '2024-01-01']))
        ->assertInvalid(['enrolled_to']);
});

test('non-numeric age bounds are rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', ['age_min' => 'young']))
        ->assertInvalid(['age_min']);
});

test('age_max below age_min is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', ['age_min' => 40, 'age_max' => 20]))
        ->assertInvalid(['age_max']);
});

test('an invalid sort column is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', ['sort' => 'phone']))
        ->assertInvalid(['sort']);
});

test('an invalid sort direction is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', ['direction' => 'sideways']))
        ->assertInvalid(['direction']);
});

test('a sort query param reorders the clients and is echoed back to the page', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Client $bravo */
    $bravo = Client::factory()->create(['organization_id' => $user->current_organization_id, 'first_name' => 'Bravo']);
    /** @var Client $alpha */
    $alpha = Client::factory()->create(['organization_id' => $user->current_organization_id, 'first_name' => 'Alpha']);

    $this->actingAs($user)
        ->get(route('clients.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clients.data.0.id', $alpha->id)
            ->where('clients.data.1.id', $bravo->id)
            ->where('sort.column', 'name')
            ->where('sort.direction', 'asc')
        );
});

test('the index page echoes the default sort when none is applied', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', 'enrollment_date')
            ->where('sort.direction', 'desc')
        );
});

test('the index page includes the gender options for the filters drawer', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where(
                'genders',
                Gender::all(),
            ));
});

test('the index page echoes back the applied filters', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index', [
            'search' => 'Aline',
            'gender' => 'female',
            'enrolled_from' => '2024-01-01',
            'enrolled_to' => '2024-06-01',
            'age_min' => 30,
            'age_max' => 60,
            'archived' => 1,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.search', 'Aline')
            ->where('filters.gender', 'female')
            ->where('filters.enrolled_from', '2024-01-01')
            ->where('filters.enrolled_to', '2024-06-01')
            ->where('filters.age_min', '30')
            ->where('filters.age_max', '60')
            ->where('filters.archived', '1'));
});

test('the index page returns null filters when none are applied', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.search', null)
            ->where('filters.gender', null)
            ->where('filters.enrolled_from', null)
            ->where('filters.enrolled_to', null)
            ->where('filters.age_min', null)
            ->where('filters.age_max', null)
            ->where('filters.archived', null));
});

test('archived clients are excluded from the index by default', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Client $active */
    $active = Client::factory()->create(['organization_id' => $user->current_organization_id]);

    Client::factory()->archived()->create(['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.id', $active->id)
        );
});

test('archived=1 returns only archived clients', function () {
    $user = User::factory()->withOrganization()->create();

    Client::factory()->create(['organization_id' => $user->current_organization_id]);

    /** @var Client $archived */
    $archived = Client::factory()->archived()->create(['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->get(route('clients.index', ['archived' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.id', $archived->id)
        );
});
