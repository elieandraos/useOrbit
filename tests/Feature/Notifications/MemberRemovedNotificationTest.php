<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\MemberRemovedNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $notification = new MemberRemovedNotification(
        $actor,
        $organization,
        ['id' => 99, 'name' => 'Jane Doe', 'email' => 'jane.doe@useorbit.com', 'role' => 'member'],
        ['id' => 100, 'name' => 'Sam Reyes'],
    );

    expect($notification->via($admin))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create(['name' => 'John Doe']);
    $admin = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create();

    $notification = new MemberRemovedNotification(
        $actor,
        $organization,
        ['id' => 99, 'name' => 'Jane Doe', 'email' => 'jane.doe@useorbit.com', 'role' => 'member'],
        ['id' => 100, 'name' => 'Sam Reyes'],
    );

    $expected = [
        'action' => 'member.removed',
        'actor' => ['id' => $actor->id, 'name' => 'John Doe'],
        'subject' => ['kind' => 'organization', 'slug' => (string) $organization->id, 'name' => 'Acme Insurance'],
        'meta' => [
            'member' => ['id' => 99, 'name' => 'Jane Doe', 'email' => 'jane.doe@useorbit.com', 'role' => 'member'],
            'successor' => ['id' => 100, 'name' => 'Sam Reyes'],
        ],
        'summary' => 'John Doe removed Jane Doe from Acme Insurance.',
    ];

    expect($notification->toArray($admin))->toBe($expected)
        ->and(MemberRemovedNotification::ACTION)->toBe('member.removed');

    $broadcast = $notification->toBroadcast($admin);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});
