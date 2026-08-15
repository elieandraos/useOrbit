<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\YourRoleChangedNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $notification = new YourRoleChangedNotification($actor, $organization, OrganizationRole::Member, OrganizationRole::Admin);

    expect($notification->via($member))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create(['name' => 'John Doe']);
    $member = User::factory()->forOrganization($organization)->create(['name' => 'Jane Doe']);

    $notification = new YourRoleChangedNotification($actor, $organization, OrganizationRole::Member, OrganizationRole::Admin);

    $expected = [
        'action' => 'member.your_role_changed',
        'actor' => ['id' => $actor->id, 'name' => 'John Doe'],
        'subject' => ['kind' => 'organization', 'slug' => (string) $organization->id, 'name' => 'Acme Insurance'],
        'meta' => [
            'from_role' => 'member',
            'to_role' => 'admin',
        ],
        'summary' => 'Your role was changed to Admin.',
    ];

    expect($notification->toArray($member))->toBe($expected)
        ->and(YourRoleChangedNotification::ACTION)->toBe('member.your_role_changed');

    $broadcast = $notification->toBroadcast($member);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});
