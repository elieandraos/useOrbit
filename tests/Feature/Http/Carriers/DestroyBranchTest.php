<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $branch = CarrierBranch::factory()->create();

    $this->delete(route('carriers.branches.destroy', $branch))
        ->assertRedirect(route('login'));
});

test('destroy redirects to carriers.show with toast on success', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->delete(route('carriers.branches.destroy', $branch))
        ->assertRedirect(route('carriers.show', $carrier))
        ->assertHasInertiaFlash('success', 'Branch deleted.');

    $this->assertModelMissing($branch);
});

test('user is forbidden from deleting a branch on a carrier from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();

    $this->actingAs($user)
        ->delete(route('carriers.branches.destroy', $branch))
        ->assertForbidden();

    $this->assertModelExists($branch);
});
