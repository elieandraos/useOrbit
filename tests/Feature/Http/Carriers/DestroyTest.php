<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->delete(route('carriers.destroy', $carrier))
        ->assertRedirect(route('login'));
});

test('owner can soft delete a carrier from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $carrier = Carrier::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)
        ->delete(route('carriers.destroy', $carrier))
        ->assertRedirect(route('carriers.index'))
        ->assertHasInertiaFlash('success', 'Carrier deleted.');

    $this->assertSoftDeleted($carrier);
});

test('non-owner member is forbidden from deleting a carrier', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->delete(route('carriers.destroy', $carrier))
        ->assertForbidden();
});
