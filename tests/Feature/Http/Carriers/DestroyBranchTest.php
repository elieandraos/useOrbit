<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Organization;
use App\Models\Policy;
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

test('destroy keeps every policy issued by the branch, with no branch', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();
    $branch = CarrierBranch::factory()->forCarrier($carrier)->create();
    $policies = Policy::factory(2)->forOrganization($user)->create([
        'created_by' => $user->id,
        'carrier_id' => $carrier->id,
        'carrier_branch_id' => $branch->id,
    ]);

    $this->actingAs($user)
        ->delete(route('carriers.branches.destroy', $branch))
        ->assertRedirect(route('carriers.show', $carrier));

    foreach ($policies as $policy) {
        expect($policy->fresh())
            ->not->toBeNull()
            ->carrier_branch_id->toBeNull();
    }
});
