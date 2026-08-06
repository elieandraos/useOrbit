<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $notification = new MemberJoinedNotification($organization, $member);

    expect($notification->via($owner))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Admin)->create([
        'name' => 'Jane Doe',
        'email' => 'jane.doe@useorbit.com',
    ]);

    $notification = new MemberJoinedNotification($organization, $member);

    $expected = [
        'action' => 'member.joined',
        'actor' => ['id' => $member->id, 'name' => 'Jane Doe'],
        'subject' => ['kind' => 'organization', 'slug' => (string) $organization->id, 'name' => 'Acme Insurance'],
        'meta' => [
            'member' => [
                'id' => $member->id,
                'name' => 'Jane Doe',
                'email' => 'jane.doe@useorbit.com',
                'role' => 'admin',
            ],
        ],
        'summary' => 'Jane Doe joined Acme Insurance.',
    ];

    expect($notification->toArray($owner))->toBe($expected)
        ->and(MemberJoinedNotification::ACTION)->toBe('member.joined');

    $broadcast = $notification->toBroadcast($owner);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});
