<?php

declare(strict_types=1);

use App\Enums\NotificationReason;
use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
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
        'meta' => ['reason' => $reason->value],
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
