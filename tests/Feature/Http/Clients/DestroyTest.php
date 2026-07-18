<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->delete(route('clients.destroy', $client))
        ->assertRedirect(route('login'));
});

test('owner can soft delete a client from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create(['current_organization_id' => $organization->id]);
    $owner->organizations()->attach($organization, [
        'role' => OrganizationRole::Owner->value,
        'status' => OrganizationMemberStatus::Active->value,
    ]);
    $client = Client::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)
        ->delete(route('clients.destroy', $client))
        ->assertRedirect(route('clients.index'))
        ->assertHasInertiaFlash('success', 'Client deleted.');

    $this->assertSoftDeleted($client);
});

test('non-owner member is forbidden from deleting a client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->delete(route('clients.destroy', $client))
        ->assertForbidden();
});
