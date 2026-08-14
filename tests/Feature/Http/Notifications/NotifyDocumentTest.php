<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Client;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use Illuminate\Support\Facades\Notification;

test('guests are redirected to the login page', function () {
    $owner = User::factory()->withOrganization()->create();
    $document = Document::factory()->forOrganization($owner)->create();

    $this->post(route('documents.notify', $document), ['reason' => 'needs_review', 'recipient_ids' => [1]])
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a document from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $document = Document::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->post(route('documents.notify', $document), ['reason' => 'needs_review', 'recipient_ids' => [$user->id]])
        ->assertNotFound();
});

test('missing reason is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->forOrganization($organization)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $document = Document::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('documents.notify', $document), ['recipient_ids' => [$recipient->id]])
        ->assertSessionHasErrors(['reason']);
});

test('notifies exactly the selected recipient with the correct envelope, including the owning client as parent', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $recipient = User::factory()->forOrganization($organization)->create();
    $notSelected = User::factory()->forOrganization($organization)->create();
    $client = Client::factory()->forOrganization($owner)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $document = Document::factory()->forOrganization($owner)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
        'original_filename' => 'policy.pdf',
    ]);

    $this->actingAs($owner)
        ->post(route('documents.notify', $document), ['reason' => 'needs_review', 'recipient_ids' => [$recipient->id]])
        ->assertRedirect();

    Notification::assertSentTo(
        $recipient,
        ResourceMessageNotification::class,
        function (ResourceMessageNotification $notification) use ($recipient, $client): bool {
            $data = $notification->toArray($recipient);

            return $data['action'] === 'resource.message'
                && $data['subject']['kind'] === 'document'
                && $data['subject']['name'] === 'policy.pdf'
                && $data['meta']['reason'] === 'needs_review'
                && $data['meta']['parent'] === ['kind' => 'client', 'slug' => $client->slug, 'name' => 'Jane Doe']
                && $data['summary'] === 'Needs your review.';
        },
    );
    Notification::assertNotSentTo($notSelected, ResourceMessageNotification::class);
});
