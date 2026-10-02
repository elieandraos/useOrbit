<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;

/**
 * The HTML of the email's header cell, where the default theme shows the app name.
 */
function invitationHeader(OrganizationInvitationNotification $notification, User $invitee): string
{
    $html = (string) $notification->toMail($invitee)->render();

    preg_match('/<td class="header"[^>]*>(.*?)<\/td>/s', $html, $matches);

    return $matches[1] ?? '';
}

test('the email header shows the inviting organization name instead of the app name', function () {
    $organization = Organization::factory()->create(['name' => 'Cedar Brokers']);
    $inviter = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $invitee = User::factory()->forOrganization($organization)->create();

    $header = invitationHeader(new OrganizationInvitationNotification($organization, $inviter, 'token'), $invitee);

    expect($header)->toContain('Cedar Brokers')
        ->not->toContain(config('app.name'));
});

test('a queued invitation sent after a rename shows the new name in its header', function () {
    $organization = Organization::factory()->create(['name' => 'Cedar Brokers']);
    $invitee = User::factory()->forOrganization($organization)->create();
    $queued = serialize(new OrganizationInvitationNotification($organization, null, 'token'));

    Organization::query()->whereKey($organization->id)->update(['name' => 'Cedar & Sons Brokers']);

    /** @var OrganizationInvitationNotification $notification */
    $notification = unserialize($queued);

    expect(invitationHeader($notification, $invitee))->toContain('Cedar &amp; Sons Brokers');
});

test('the email keeps the app as its sender', function () {
    $organization = Organization::factory()->create(['name' => 'Cedar Brokers']);
    $invitee = User::factory()->forOrganization($organization)->create();

    $mail = new OrganizationInvitationNotification($organization, null, 'token')->toMail($invitee);

    expect($mail->from)->toBe([]);
});
