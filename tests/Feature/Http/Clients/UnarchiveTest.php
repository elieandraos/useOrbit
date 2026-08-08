<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
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
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $client = Client::factory()->forOrganization($owner)->archived()->create();

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
    $client = Client::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->patch(route('clients.unarchive', $client))
        ->assertForbidden();
});
