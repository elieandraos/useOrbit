<?php

declare(strict_types=1);

use App\Enums\CarrierStatus;
use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->patch(route('carriers.archive', $carrier))
        ->assertRedirect(route('login'));
});

test('owner can archive a carrier from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
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

test('notifies leadership that the carrier was archived, excluding the actor and plain members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    $carrier = Carrier::factory()->forOrganization($owner)->create();

    $this->actingAs($owner)->patch(route('carriers.archive', $carrier));

    Notification::assertSentTo(
        $admin,
        ResourceArchivedNotification::class,
        fn (ResourceArchivedNotification $notification): bool => $notification->toArray($admin)['subject']['kind'] === 'carrier',
    );
    Notification::assertNotSentTo($owner, ResourceArchivedNotification::class);
    Notification::assertNotSentTo($member, ResourceArchivedNotification::class);
});
