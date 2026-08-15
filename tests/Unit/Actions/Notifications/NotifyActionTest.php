<?php

declare(strict_types=1);

use App\Actions\Notifications\NotifyAction;
use App\Enums\NotificationReason;
use App\Enums\OrganizationMemberStatus;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\Notification;

test('notifies exactly the valid recipient, excluding the actor, a suspended id, and a cross-org id mixed into the list', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization)->create();
    $validRecipient = User::factory()->forOrganization($organization)->create();
    $suspendedRecipient = User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Suspended]);
    $crossOrgRecipient = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($actor)->create();
    app(OrganizationContext::class)->set($organization->id);

    app(NotifyAction::class)->handle(
        $actor,
        $carrier,
        [$validRecipient->id, $actor->id, $suspendedRecipient->id, $crossOrgRecipient->id],
        NotificationReason::NeedsReview,
    );

    Notification::assertSentTo($validRecipient, ResourceMessageNotification::class);
    Notification::assertNotSentTo($actor, ResourceMessageNotification::class);
    Notification::assertNotSentTo($suspendedRecipient, ResourceMessageNotification::class);
    Notification::assertNotSentTo($crossOrgRecipient, ResourceMessageNotification::class);
});

test('passes the selected reason through unchanged into the sent notification', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->forOrganization($actor)->create();
    app(OrganizationContext::class)->set($organization->id);

    app(NotifyAction::class)->handle($actor, $carrier, [$recipient->id], NotificationReason::WantsInput);

    Notification::assertSentTo(
        $recipient,
        ResourceMessageNotification::class,
        fn (ResourceMessageNotification $notification): bool => $notification->toArray($recipient)['meta']['reason'] === 'wants_input',
    );
});
