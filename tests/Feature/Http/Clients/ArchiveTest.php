<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->patch(route('clients.archive', $client))
        ->assertRedirect(route('login'));
});

test('owner can archive a client from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $client = Client::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)
        ->patch(route('clients.archive', $client))
        ->assertRedirect(route('clients.index'))
        ->assertHasInertiaFlash('success', 'Client archived.');

    /** @var Client $fresh */
    $fresh = $client->fresh();
    expect($fresh->status)->toBe(ClientStatus::Archived);

    $this->assertNotSoftDeleted($client);
});

test('non-owner member is forbidden from archiving a client', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('clients.archive', $client))
        ->assertForbidden();
});

test('notifies leadership that the client was archived, excluding the actor and plain members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    $client = Client::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)->patch(route('clients.archive', $client));

    Notification::assertSentTo(
        $admin,
        ResourceArchivedNotification::class,
        fn (ResourceArchivedNotification $notification): bool => $notification->toArray($admin)['subject']['kind'] === 'client',
    );
    Notification::assertNotSentTo($owner, ResourceArchivedNotification::class);
    Notification::assertNotSentTo($member, ResourceArchivedNotification::class);
});
