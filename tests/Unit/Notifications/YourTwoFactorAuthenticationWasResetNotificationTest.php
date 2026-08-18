<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\YourTwoFactorAuthenticationWasResetNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

test('is delivered via the database and broadcast channels', function () {
    $organization = Organization::factory()->create();
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->create();

    $notification = new YourTwoFactorAuthenticationWasResetNotification($actor, $organization);

    expect($notification->via($member))->toBe(['database', 'broadcast']);
});

test('array and broadcast payloads carry the action, actor, subject, meta, and a summary', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $actor = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create(['name' => 'John Doe']);
    $member = User::factory()->forOrganization($organization)->create(['name' => 'Jane Doe']);

    $notification = new YourTwoFactorAuthenticationWasResetNotification($actor, $organization);

    $expected = [
        'action' => 'member.your_two_factor_reset',
        'actor' => ['id' => $actor->id, 'name' => 'John Doe'],
        'subject' => ['kind' => 'organization', 'slug' => (string) $organization->id, 'name' => 'Acme Insurance'],
        'meta' => [],
        'summary' => 'John Doe reset your two-factor authentication.',
    ];

    expect($notification->toArray($member))->toBe($expected)
        ->and(YourTwoFactorAuthenticationWasResetNotification::ACTION)->toBe('member.your_two_factor_reset');

    $broadcast = $notification->toBroadcast($member);
    expect($broadcast)->toBeInstanceOf(BroadcastMessage::class)
        ->and($broadcast->data)->toBe($expected);
});

test('carries no actor and a generic summary for an operator-mediated reset', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Insurance']);
    $member = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    $notification = new YourTwoFactorAuthenticationWasResetNotification(null, $organization);

    expect($notification->toArray($member))->toBe([
        'action' => 'member.your_two_factor_reset',
        'actor' => null,
        'subject' => ['kind' => 'organization', 'slug' => (string) $organization->id, 'name' => 'Acme Insurance'],
        'meta' => [],
        'summary' => 'Your two-factor authentication was reset.',
    ]);
});
