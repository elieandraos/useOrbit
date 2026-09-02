<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceArchivedNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $actor = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($actor)->create();

    $notification = new ResourceArchivedNotification($actor, $carrier);

    expect($notification->via($actor))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $actor = User::factory()->withOrganization()->create(['name' => 'Sarah Cohen']);
    $carrier = Carrier::factory()->forOrganization($actor)->create(['name' => 'Acme Freight']);

    $notification = new ResourceArchivedNotification($actor, $carrier);

    $expected = [
        'action' => 'resource.archived',
        'actor' => ['id' => $actor->id, 'name' => 'Sarah Cohen'],
        'subject' => ['kind' => 'carrier', 'slug' => $carrier->slug, 'name' => 'Acme Freight'],
        'meta' => [],
        'summary' => 'Sarah Cohen archived carrier Acme Freight.',
    ];

    expect($notification->toArray($actor))->toBe($expected)
        ->and(ResourceArchivedNotification::ACTION)->toBe('resource.archived');

    $broadcast = $notification->toBroadcast($actor);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});
