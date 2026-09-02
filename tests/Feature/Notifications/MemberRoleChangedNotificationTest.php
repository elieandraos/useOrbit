<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\MemberRoleChangedNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $notification = new MemberRoleChangedNotification($actor, $organization, $member, OrganizationRole::Member, OrganizationRole::Admin);

    expect($notification->via($actor))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create(['name' => 'John Doe']);
    $member = User::factory()->forOrganization($organization)->create([
        'name' => 'Jane Doe',
        'email' => 'jane.doe@useorbit.com',
    ]);

    $notification = new MemberRoleChangedNotification($actor, $organization, $member, OrganizationRole::Member, OrganizationRole::Admin);

    $expected = [
        'action' => 'member.role_changed',
        'actor' => ['id' => $actor->id, 'name' => 'John Doe'],
        'subject' => ['kind' => 'organization', 'slug' => (string) $organization->id, 'name' => 'Acme Insurance'],
        'meta' => [
            'member' => [
                'id' => $member->id,
                'name' => 'Jane Doe',
                'email' => 'jane.doe@useorbit.com',
            ],
            'from_role' => 'member',
            'to_role' => 'admin',
        ],
        'summary' => "John Doe changed Jane Doe's role from Member to Admin.",
    ];

    expect($notification->toArray($actor))->toBe($expected)
        ->and(MemberRoleChangedNotification::ACTION)->toBe('member.role_changed');

    $broadcast = $notification->toBroadcast($actor);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});
