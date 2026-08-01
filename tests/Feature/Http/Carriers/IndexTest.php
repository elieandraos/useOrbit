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
            CarrierResource::collection(Carrier::query()->with('hqBranch')->latest('onboarded_date')->paginate(7))
        );
});

test('carriers are ordered by onboarded date, newest first', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $oldest */
    $oldest = Carrier::factory()->forOrganization($user)->create(['onboarded_date' => '2023-01-01']);
    /** @var Carrier $newest */
    $newest = Carrier::factory()->forOrganization($user)->create(['onboarded_date' => '2024-06-01']);
    /** @var Carrier $middle */
    $middle = Carrier::factory()->forOrganization($user)->create(['onboarded_date' => '2024-01-01']);

    $this->actingAs($user)
        ->get(route('carriers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('carriers.data.0.id', $newest->id)
            ->where('carriers.data.1.id', $middle->id)
            ->where('carriers.data.2.id', $oldest->id)
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
                Carrier::query()->where('organization_id', $user->current_organization_id)->with('hqBranch')->latest('onboarded_date')->paginate(7)
            )
        );
});
