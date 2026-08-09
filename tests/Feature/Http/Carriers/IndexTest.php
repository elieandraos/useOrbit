<?php

declare(strict_types=1);

use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('carriers.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can list their organization carriers', function () {
    $user = User::factory()->withOrganization()->create();
    $carriers = Carrier::factory(2)->forOrganization($user)->create();
    $carriers->each(fn (Carrier $carrier) => CarrierBranch::factory()->forCarrier($carrier)->create());

    $this->assertDatabaseCount('carriers', 2);

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'carriers',
            CarrierResource::collection(Carrier::query()->with('branches')->orderBy('name')->paginate(7))
        );
});

test('carriers are ordered by name, A to Z', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->forOrganization($user)->create(['name' => 'Bravo Assurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->forOrganization($user)->create(['name' => 'Alpha Assurance']);
    /** @var Carrier $charlie */
    $charlie = Carrier::factory()->forOrganization($user)->create(['name' => 'Charlie Assurance']);

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('carriers.data.0.id', $alpha->id)
            ->where('carriers.data.1.id', $bravo->id)
            ->where('carriers.data.2.id', $charlie->id)
        );
});

test('an invalid sort column is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('carriers.index', ['sort' => 'phone']))
        ->assertInvalid(['sort']);
});

test('an invalid sort direction is rejected', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('carriers.index', ['direction' => 'sideways']))
        ->assertInvalid(['direction']);
});

test('a sort query param reorders the carriers and is echoed back to the page', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $bravo */
    $bravo = Carrier::factory()->forOrganization($user)->create(['name' => 'Bravo Assurance']);
    /** @var Carrier $alpha */
    $alpha = Carrier::factory()->forOrganization($user)->create(['name' => 'Alpha Assurance']);

    $this->actingAs($user)
        ->get(route('carriers.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('carriers.data.0.id', $bravo->id)
            ->where('carriers.data.1.id', $alpha->id)
            ->where('sort.column', 'name')
            ->where('sort.direction', 'desc')
        );
});

test('the index page echoes the default sort when none is applied', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sort.column', 'name')
            ->where('sort.direction', 'asc')
        );
});

test('a search query param narrows the carriers and is echoed back to the page', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $match */
    $match = Carrier::factory()->forOrganization($user)->create(['name' => 'Alpha Assurance']);
    Carrier::factory()->forOrganization($user)->create(['name' => 'Bravo Insurance']);

    $this->actingAs($user)
        ->get(route('carriers.index', ['search' => 'Alpha']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('carriers.data', 1)
            ->where('carriers.data.0.id', $match->id)
            ->where('filters.search', 'Alpha')
        );
});

test('the index only shows active carriers by default', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $active */
    $active = Carrier::factory()->forOrganization($user)->create();
    Carrier::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('carriers.data', 1)
            ->where('carriers.data.0.id', $active->id)
            ->where('filters.archived', false)
        );
});

test('an archived query param shows only archived carriers', function () {
    $user = User::factory()->withOrganization()->create();

    Carrier::factory()->forOrganization($user)->create();
    /** @var Carrier $archived */
    $archived = Carrier::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('carriers.index', ['archived' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('carriers.data', 1)
            ->where('carriers.data.0.id', $archived->id)
            ->where('filters.archived', true)
        );
});

test('carriers from another organization are not included', function () {
    $user = User::factory()->withOrganization()->create();
    Carrier::factory(2)->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Carrier::factory(3)->for($otherOrganization)->create();

    $this->assertDatabaseCount('carriers', 5);

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'carriers',
            CarrierResource::collection(
                Carrier::query()->where('organization_id', $user->organization_id)->with('branches')->orderBy('name')->paginate(7)
            )
        );
});
