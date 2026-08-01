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
            CarrierResource::collection(Carrier::query()->with('hqBranch')->orderBy('name')->paginate(7))
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
                Carrier::query()->where('organization_id', $user->current_organization_id)->with('hqBranch')->orderBy('name')->paginate(7)
            )
        );
});
