<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $owner = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($owner)->create();

    $this->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [1]])
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOwner = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($otherOwner)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [$user->id]])
        ->assertNotFound();
});

test('missing reason is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['recipient_ids' => [$recipient->id]])
        ->assertSessionHasErrors(['reason']);
});

test('invalid reason value is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'urgent', 'recipient_ids' => [$recipient->id]])
        ->assertSessionHasErrors(['reason']);
});

test('empty recipient_ids is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => []])
        ->assertSessionHasErrors(['recipient_ids']);
});

test('duplicate recipient id is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [$recipient->id, $recipient->id]])
        ->assertSessionHasErrors(['recipient_ids.0', 'recipient_ids.1']);
});

test("actor's own id is rejected", function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [$user->id]])
        ->assertSessionHasErrors(['recipient_ids.0']);
});

test('cross-org recipient id is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $outsider = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [$outsider->id]])
        ->assertSessionHasErrors(['recipient_ids.0']);
});

test('suspended recipient is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $suspended = User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Suspended]);
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [$suspended->id]])
        ->assertSessionHasErrors(['recipient_ids.0']);
});

test('invited recipient is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $invited = User::factory()->forOrganization($organization)->create(['status' => OrganizationMemberStatus::Invited]);
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notify', $client), ['reason' => 'needs_review', 'recipient_ids' => [$invited->id]])
        ->assertSessionHasErrors(['recipient_ids.0']);
});

test('notifies exactly the selected recipients with the correct envelope', function (string $reason, string $summary) {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $notSelected = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($owner)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $this->actingAs($owner)
        ->post(route('clients.notify', $client), ['reason' => $reason, 'recipient_ids' => [$recipient->id]])
        ->assertRedirect();

    Notification::assertSentTo(
        $recipient,
        ResourceMessageNotification::class,
        function (ResourceMessageNotification $notification) use ($recipient, $reason, $summary): bool {
            $data = $notification->toArray($recipient);

            return $data['action'] === 'resource.message'
                && $data['subject']['kind'] === 'client'
                && $data['meta']['reason'] === $reason
                && $data['summary'] === $summary;
        },
    );
    Notification::assertNotSentTo($notSelected, ResourceMessageNotification::class);
})->with([
    'needs_review' => ['needs_review', 'Needs your review.'],
    'for_attention' => ['for_attention', 'For your attention.'],
    'wants_input' => ['wants_input', 'Would appreciate your input.'],
]);
