<?php

declare(strict_types=1);

use App\Enums\CarrierStatus;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->patch(route('carriers.archive', $carrier))
        ->assertRedirect(route('login'));
});

test('owner can archive a carrier from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create(['current_organization_id' => $organization->id]);
    $owner->organizations()->attach($organization, [
        'role' => OrganizationRole::Owner->value,
        'status' => OrganizationMemberStatus::Active->value,
    ]);
    $carrier = Carrier::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)
        ->patch(route('carriers.archive', $carrier))
        ->assertRedirect(route('carriers.index'))
        ->assertHasInertiaFlash('success', 'Carrier archived.');

    /** @var Carrier $fresh */
    $fresh = $carrier->fresh();
    expect($fresh->status)->toBe(CarrierStatus::Archived);

    $this->assertNotSoftDeleted($carrier);
});

test('non-owner member is forbidden from archiving a carrier', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('carriers.archive', $carrier))
        ->assertForbidden();
});
