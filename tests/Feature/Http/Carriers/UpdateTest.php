<?php

declare(strict_types=1);

use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'name' => 'Beta Traders',
    'phone' => '+961 1 555 555',
    'website' => 'beta-traders.com.lb',
];

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->get(route('carriers.edit', $carrier))->assertRedirect(route('login'));
    $this->patch(route('carriers.update', $carrier))->assertRedirect(route('login'));
});

test('edit page renders with carrier data', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->get(route('carriers.edit', $carrier))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Carriers/Edit')
            ->hasResource('carrier', CarrierResource::make($carrier->load('updatedBy')))
        );
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->patch(route('carriers.update', $carrier))
        ->assertSessionHasErrors(['name']);
});

test('update redirects to carriers.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->patch(route('carriers.update', $carrier), $validPayload)
        ->assertRedirect(route('carriers.show', $carrier->fresh()))
        ->assertHasInertiaFlash('success', 'Carrier updated.');
});

test('user gets 404 when updating a carrier from another organization', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->patch(route('carriers.update', $carrier), $validPayload)
        ->assertNotFound();
});
