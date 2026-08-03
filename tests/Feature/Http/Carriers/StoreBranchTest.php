<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'street' => 'Hamra Street',
    'building_floor' => '4th Floor',
    'city' => 'Tripoli',
    'contact_name' => 'Nadine Fares',
    'contact_role' => 'Branch Manager',
    'contact_email' => 'nadine.fares@bankers.com.lb',
    'contact_phone' => '+961 3 555 111',
];

test('guests are redirected to the login page', function () use ($validPayload) {
    $carrier = Carrier::factory()->create();

    $this->post(route('carriers.branches.store', $carrier), $validPayload)
        ->assertRedirect(route('login'));
});

test('store returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('carriers.branches.store', $carrier))
        ->assertSessionHasErrors(['city', 'contact_name']);
});

test('store redirects to carriers.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('carriers.branches.store', $carrier), $validPayload)
        ->assertRedirect(route('carriers.show', $carrier))
        ->assertHasInertiaFlash('success', 'Branch added.');

    expect($carrier->fresh()->branches)->toHaveCount(1);
});

test('adds a branch without removing existing branches', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->post(route('carriers.branches.store', $carrier), $validPayload)
        ->assertRedirect(route('carriers.show', $carrier));

    expect($carrier->fresh()->branches)->toHaveCount(2);
});

test('user gets 404 when adding a branch to a carrier from another organization', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->post(route('carriers.branches.store', $carrier), $validPayload)
        ->assertNotFound();
});
