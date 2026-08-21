<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\YourTwoFactorAuthenticationWasResetNotification;
use Illuminate\Support\Facades\Notification;

test('resets the owner two-factor authentication after confirmation', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->artisan('organizations:reset-owner-two-factor', ['email' => $owner->email])
        ->expectsConfirmation("Reset two-factor authentication for {$owner->email}? This cannot be undone.", 'yes')
        ->assertSuccessful();

    expect($owner->fresh()->two_factor_secret)->toBeNull()
        ->and($owner->fresh()->two_factor_recovery_codes)->toBeNull()
        ->and($owner->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('requires explicit confirmation before resetting', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    $this->artisan('organizations:reset-owner-two-factor', ['email' => $owner->email])
        ->expectsConfirmation("Reset two-factor authentication for {$owner->email}? This cannot be undone.", 'no')
        ->assertFailed();

    expect($owner->fresh()->two_factor_secret)->not->toBeNull();
});

test('notifies the affected owner with no actor attached', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->artisan('organizations:reset-owner-two-factor', ['email' => $owner->email])
        ->expectsConfirmation("Reset two-factor authentication for {$owner->email}? This cannot be undone.", 'yes')
        ->assertSuccessful();

    Notification::assertSentTo(
        $owner,
        YourTwoFactorAuthenticationWasResetNotification::class,
        fn (YourTwoFactorAuthenticationWasResetNotification $notification): bool => $notification->toArray($owner)['actor'] === null,
    );
});

test('succeeds regardless of how many owners the organization has', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();
    User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->artisan('organizations:reset-owner-two-factor', ['email' => $owner->email])
        ->expectsConfirmation("Reset two-factor authentication for {$owner->email}? This cannot be undone.", 'yes')
        ->assertSuccessful();

    expect($owner->fresh()->two_factor_secret)->toBeNull();
});

test('fails when no owner exists with the given email', function () {
    $this->artisan('organizations:reset-owner-two-factor', ['email' => 'nobody@useorbit.com'])
        ->assertFailed();
});

test('fails when the email belongs to a non-owner member', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    $this->artisan('organizations:reset-owner-two-factor', ['email' => $member->email])
        ->assertFailed();

    expect($member->fresh()->two_factor_secret)->not->toBeNull();
});
