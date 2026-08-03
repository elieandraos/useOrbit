<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

$validPayload = [
    'street' => 'Boulevard Riad El Solh',
    'building_floor' => 'Azar Bldg, 3rd Fl',
    'city' => 'Tripoli',
    'contact_name' => 'Rami Haddad',
    'contact_role' => 'Regional Manager',
    'contact_email' => 'rami.haddad@bankers.com.lb',
    'contact_phone' => '+961 3 162 408',
];

test('guests are redirected to the login page', function () use ($validPayload) {
    $branch = CarrierBranch::factory()->create();

    $this->patch(route('carriers.branches.update', $branch), $validPayload)
        ->assertRedirect(route('login'));
});

test('update returns validation errors when required fields are missing', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->patch(route('carriers.branches.update', $branch))
        ->assertSessionHasErrors(['city', 'contact_name']);
});

test('update redirects to carriers.show with toast on success', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->patch(route('carriers.branches.update', $branch), $validPayload)
        ->assertRedirect(route('carriers.show', $carrier))
        ->assertHasInertiaFlash('success', 'Branch updated.');

    expect($branch->fresh()->city)->toBe('Tripoli');
});

test('user is forbidden from updating a branch on a carrier from another organization', function () use ($validPayload) {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->patch(route('carriers.branches.update', $branch), $validPayload)
        ->assertForbidden();
});
