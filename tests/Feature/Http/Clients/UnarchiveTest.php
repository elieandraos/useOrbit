<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->archived()->create();

    $this->patch(route('clients.unarchive', $client))
        ->assertRedirect(route('login'));
});

test('owner can unarchive a client from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->create(['current_organization_id' => $organization->id]);
    $owner->organizations()->attach($organization, [
        'role' => OrganizationRole::Owner->value,
        'status' => OrganizationMemberStatus::Active->value,
    ]);
    $client = Client::factory()->archived()->create(['organization_id' => $organization->id]);

    $this->actingAs($owner)
        ->patch(route('clients.unarchive', $client))
        ->assertRedirect(route('clients.index'))
        ->assertHasInertiaFlash('success', 'Client unarchived.');

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->status)->toBe(ClientStatus::Active);
});

test('non-owner member is forbidden from unarchiving a client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->archived()->create(['organization_id' => $user->current_organization_id]);

    $this->actingAs($user)
        ->patch(route('clients.unarchive', $client))
        ->assertForbidden();
});
