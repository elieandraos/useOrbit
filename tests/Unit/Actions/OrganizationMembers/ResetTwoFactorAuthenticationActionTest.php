<?php

declare(strict_types=1);

use App\Actions\OrganizationMembers\ResetTwoFactorAuthenticationAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\YourTwoFactorAuthenticationWasResetNotification;
use Illuminate\Support\Facades\Notification;

test('clears the member two-factor authentication columns', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ResetTwoFactorAuthenticationAction::class)->handle($owner, $member);

    expect($member->fresh()->two_factor_secret)->toBeNull()
        ->and($member->fresh()->two_factor_recovery_codes)->toBeNull()
        ->and($member->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('notifies the affected member that their two-factor authentication was reset', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $owner = User::factory()->forOrganization($organization, OrganizationRole::Owner)->create();
    $member = User::factory()->forOrganization($organization)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ResetTwoFactorAuthenticationAction::class)->handle($owner, $member);

    /** @noinspection PhpUnhandledExceptionInspection */
    Notification::assertSentTo(
        $member,
        YourTwoFactorAuthenticationWasResetNotification::class,
        fn (YourTwoFactorAuthenticationWasResetNotification $notification): bool => $notification->toArray($member)['subject']['name'] === $organization->name
            && $notification->toArray($member)['actor']['id'] === $owner->id,
    );
});

test('clears the member two-factor authentication columns with no actor, for operator-mediated resets', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $member = User::factory()->forOrganization($organization, OrganizationRole::Owner)->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(ResetTwoFactorAuthenticationAction::class)->handle(null, $member);

    expect($member->fresh()->two_factor_secret)->toBeNull()
        ->and($member->fresh()->two_factor_recovery_codes)->toBeNull()
        ->and($member->fresh()->two_factor_confirmed_at)->toBeNull();

    /** @noinspection PhpUnhandledExceptionInspection */
    Notification::assertSentTo(
        $member,
        YourTwoFactorAuthenticationWasResetNotification::class,
        fn (YourTwoFactorAuthenticationWasResetNotification $notification): bool => $notification->toArray($member)['actor'] === null,
    );
});
