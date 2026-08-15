<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $owner = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($owner)->create();

    $this->post(route('carriers.notify', $carrier), ['reason' => 'needs_review', 'recipient_ids' => [1]])
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a carrier from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOwner = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($otherOwner)->create();

    $this->actingAs($user)
        ->post(route('carriers.notify', $carrier), ['reason' => 'needs_review', 'recipient_ids' => [$user->id]])
        ->assertNotFound();
});

test('missing reason is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('carriers.notify', $carrier), ['recipient_ids' => [$recipient->id]])
        ->assertSessionHasErrors(['reason']);
});

test('notifies exactly the selected recipient with the correct envelope', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $notSelected = User::factory()->forOrganization($organization)->create();
    $carrier = Carrier::factory()->forOrganization($owner)->create(['name' => 'Acme Freight']);

    $this->actingAs($owner)
        ->post(route('carriers.notify', $carrier), ['reason' => 'needs_review', 'recipient_ids' => [$recipient->id]])
        ->assertRedirect();

    Notification::assertSentTo(
        $recipient,
        ResourceMessageNotification::class,
        function (ResourceMessageNotification $notification) use ($recipient): bool {
            $data = $notification->toArray($recipient);

            return $data['action'] === 'resource.message'
                && $data['subject']['kind'] === 'carrier'
                && $data['subject']['name'] === 'Acme Freight'
                && $data['meta']['reason'] === 'needs_review'
                && $data['summary'] === 'Needs your review.';
        },
    );
    Notification::assertNotSentTo($notSelected, ResourceMessageNotification::class);
});
