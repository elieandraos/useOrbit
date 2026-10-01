<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
            CarrierResource::collection(Carrier::query()->withCount('policies')->withClientsCount()->with('branches')->orderBy('name')->orderBy('id')->paginate(7))
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
                Carrier::query()->withCount('policies')->withClientsCount()->where('organization_id', $user->organization_id)->with('branches')->orderBy('name')->orderBy('id')->paginate(7)
            )
        );
});

test('each carrier row counts its live policies of any status and its distinct clients', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create(['name' => 'Alpha Assurance']);
    $repeatClient = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id, 'client_id' => $repeatClient->id]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id, 'client_id' => $repeatClient->id, 'status' => PolicyStatus::Cancelled->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id, 'client_id' => $otherClient->id, 'status' => PolicyStatus::Frozen->value]);
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id, 'client_id' => Client::factory()->forOrganization($user)])->delete();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => Carrier::factory()->forOrganization($user)->create(['name' => 'Zulu Assurance']), 'client_id' => $repeatClient->id]);

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('carriers.data.0.id', $carrier->id)
            ->where('carriers.data.0.policies_count', 3)
            ->where('carriers.data.0.clients_count', 2)
        );
});

test('a soft-deleted client is left out of the carrier client count while its policies still count', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    $deletedClient = Client::factory()->forOrganization($user)->create();
    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => $carrier->id, 'client_id' => $deletedClient->id]);
    $deletedClient->delete();

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('carriers.data.0.policies_count', 1)
            ->where('carriers.data.0.clients_count', 0)
        );
});

test('policies and clients from another organization are not counted on a carrier row', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $otherOrganization = Organization::factory()->create();
    Policy::factory()->for($otherOrganization)->create([
        'carrier_id' => $carrier->id,
        'client_id' => Client::factory()->for($otherOrganization),
    ]);

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('carriers.data.0.policies_count', 0)
            ->where('carriers.data.0.clients_count', 0)
        );
});

test('the carrier index runs the same number of queries regardless of how many rows have counts', function () {
    $user = User::factory()->withOrganization()->create();
    $countQueries = function () use ($user): int {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($user)->get(route('carriers.index'))->assertOk();

        return $queries;
    };

    $countQueries(); // warm up one-off, per-process queries

    Policy::factory()->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => Carrier::factory()->forOrganization($user), 'client_id' => Client::factory()->forOrganization($user)]);
    $queriesForOneRow = $countQueries();

    Policy::factory(4)->forOrganization($user)->create(['created_by' => $user->id, 'carrier_id' => fn () => Carrier::factory()->forOrganization($user)->create()->id, 'client_id' => fn () => Client::factory()->forOrganization($user)->create()->id]);
    $queriesForFiveRows = $countQueries();

    expect($queriesForFiveRows)->toBe($queriesForOneRow);
});
