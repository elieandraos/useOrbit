<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $owner = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($owner)->create();

    $this->post(route('agents.notify', $agent), ['reason' => 'needs_review', 'recipient_ids' => [1]])
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for an agent from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOwner = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($otherOwner)->create();

    $this->actingAs($user)
        ->post(route('agents.notify', $agent), ['reason' => 'needs_review', 'recipient_ids' => [$user->id]])
        ->assertNotFound();
});

test('missing reason is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $agent = Agent::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('agents.notify', $agent), ['recipient_ids' => [$recipient->id]])
        ->assertSessionHasErrors(['reason']);
});

test('notifies exactly the selected recipient with the correct envelope', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $notSelected = User::factory()->forOrganization($organization)->create();
    $agent = Agent::factory()->forOrganization($owner)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $this->actingAs($owner)
        ->post(route('agents.notify', $agent), ['reason' => 'needs_review', 'recipient_ids' => [$recipient->id]])
        ->assertRedirect();

    Notification::assertSentTo(
        $recipient,
        ResourceMessageNotification::class,
        function (ResourceMessageNotification $notification) use ($recipient): bool {
            $data = $notification->toArray($recipient);

            return $data['action'] === 'resource.message'
                && $data['subject']['kind'] === 'agent'
                && $data['subject']['name'] === 'Jane Doe'
                && $data['meta']['reason'] === 'needs_review'
                && $data['summary'] === 'Needs your review.';
        },
    );
    Notification::assertNotSentTo($notSelected, ResourceMessageNotification::class);
});
