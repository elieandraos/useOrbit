<?php

declare(strict_types=1);

use App\Enums\CarrierStatus;
use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->archived()->create();

    $this->patch(route('carriers.unarchive', $carrier))
        ->assertRedirect(route('login'));
});

test('owner can unarchive a carrier from their organization', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $carrier = Carrier::factory()->forOrganization($owner)->archived()->create();

    $this->actingAs($owner)
        ->patch(route('carriers.unarchive', $carrier))
        ->assertRedirect(route('carriers.index'))
        ->assertHasInertiaFlash('success', 'Carrier unarchived.');

    /** @var Carrier $fresh */
    $fresh = $carrier->fresh();
    expect($fresh->status)->toBe(CarrierStatus::Active);
});

test('non-owner member is forbidden from unarchiving a carrier', function () {
    $user = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($user)->archived()->create();

    $this->actingAs($user)
        ->patch(route('carriers.unarchive', $carrier))
        ->assertForbidden();
});

test('notifies leadership that the carrier was unarchived, excluding the actor and plain members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Member)->create();
    $carrier = Carrier::factory()->forOrganization($owner)->archived()->create();

    $this->actingAs($owner)->patch(route('carriers.unarchive', $carrier));

    Notification::assertSentTo(
        $admin,
        ResourceUnarchivedNotification::class,
        fn (ResourceUnarchivedNotification $notification): bool => $notification->toArray($admin)['subject']['kind'] === 'carrier',
    );
    Notification::assertNotSentTo($owner, ResourceUnarchivedNotification::class);
    Notification::assertNotSentTo($member, ResourceUnarchivedNotification::class);
});
