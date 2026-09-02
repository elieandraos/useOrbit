<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\User;
use App\Notifications\ResourceUnarchivedNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $actor = User::factory()->withOrganization()->create();
    $carrier = Carrier::factory()->forOrganization($actor)->archived()->create();

    $notification = new ResourceUnarchivedNotification($actor, $carrier);

    expect($notification->via($actor))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $actor = User::factory()->withOrganization()->create(['name' => 'Sarah Cohen']);
    $carrier = Carrier::factory()->forOrganization($actor)->archived()->create(['name' => 'Acme Freight']);

    $notification = new ResourceUnarchivedNotification($actor, $carrier);

    $expected = [
        'action' => 'resource.unarchived',
        'actor' => ['id' => $actor->id, 'name' => 'Sarah Cohen'],
        'subject' => ['kind' => 'carrier', 'slug' => $carrier->slug, 'name' => 'Acme Freight'],
        'meta' => [],
        'summary' => 'Sarah Cohen unarchived carrier Acme Freight.',
    ];

    expect($notification->toArray($actor))->toBe($expected)
        ->and(ResourceUnarchivedNotification::ACTION)->toBe('resource.unarchived');

    $broadcast = $notification->toBroadcast($actor);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});
