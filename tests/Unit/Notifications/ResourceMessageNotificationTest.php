<?php

declare(strict_types=1);

use App\Enums\NotificationReason;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $actor = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($actor)->create();

    $notification = new ResourceMessageNotification($actor, $carrier, NotificationReason::NeedsReview);

    expect($notification->via($actor))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function (NotificationReason $reason, string $summary) {
    $actor = User::factory()->withOrganization()->create(['name' => 'Sarah Cohen']);
    $carrier = Carrier::factory()->forOrganization($actor)->create(['name' => 'Acme Freight']);

    $notification = new ResourceMessageNotification($actor, $carrier, $reason);

    $expected = [
        'action' => 'resource.message',
        'actor' => ['id' => $actor->id, 'name' => 'Sarah Cohen'],
        'subject' => ['kind' => 'carrier', 'slug' => $carrier->slug, 'name' => 'Acme Freight'],
        'meta' => ['reason' => $reason->value, 'parent' => null],
        'summary' => $summary,
    ];

    expect($notification->toArray($actor))->toBe($expected)
        ->and(ResourceMessageNotification::ACTION)->toBe('resource.message');

    $broadcast = $notification->toBroadcast($actor);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
})->with([
    'needs_review' => [NotificationReason::NeedsReview, 'Needs your review.'],
    'for_attention' => [NotificationReason::ForAttention, 'For your attention.'],
    'wants_input' => [NotificationReason::WantsInput, 'Would appreciate your input.'],
]);

test('meta.parent is null for a resource without a notification parent', function () {
    $actor = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($actor)->create();

    $notification = new ResourceMessageNotification($actor, $carrier, NotificationReason::NeedsReview);

    expect($notification->toArray($actor)['meta']['parent'])->toBeNull();
});

test('meta.parent carries the owning client for a document', function () {
    $actor = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($actor)->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $document = Document::factory()->forOrganization($actor)->create([
        'documentable_type' => $client->getMorphClass(),
        'documentable_id' => $client->id,
    ]);
    app(OrganizationContext::class)->set($actor->organization_id);

    $notification = new ResourceMessageNotification($actor, $document, NotificationReason::NeedsReview);

    expect($notification->toArray($actor)['meta']['parent'])->toBe([
        'kind' => 'client',
        'slug' => $client->slug,
        'name' => 'Jane Doe',
    ]);
});
