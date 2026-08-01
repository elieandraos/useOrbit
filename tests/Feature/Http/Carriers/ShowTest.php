<?php

declare(strict_types=1);

use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->get(route('carriers.show', $carrier))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a carrier from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->get(route('carriers.show', $carrier))
        ->assertOk()
        ->assertHasResource('carrier', CarrierResource::make($carrier->load('hqBranch')));

    $this->assertDatabaseHas('carriers', ['slug' => $carrier->slug]);
});

test('authenticated user gets 404 for a carrier from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('carriers.show', $carrier))
        ->assertNotFound();
});

test('an archived carrier can still be shown', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->archived()->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->get(route('carriers.show', $carrier))
        ->assertOk();
});
