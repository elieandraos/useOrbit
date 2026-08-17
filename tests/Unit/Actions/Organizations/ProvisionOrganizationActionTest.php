<?php

declare(strict_types=1);

use App\Actions\Organizations\ProvisionOrganizationAction;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;

$attributes = [
    'organization' => 'Acme Insurance',
    'name' => 'Jane Owner',
    'email' => 'jane.owner@useorbit.com',
];

test('creates an organization and its first owner with no password', function () use ($attributes) {
    /** @noinspection PhpUnhandledExceptionInspection */
    ['owner' => $owner] = app(ProvisionOrganizationAction::class)->handle($attributes);

    expect(Organization::query()->where('name', 'Acme Insurance')->exists())->toBeTrue()
        ->and($owner->name)->toBe('Jane Owner')
        ->and($owner->email)->toBe('jane.owner@useorbit.com')
        ->and($owner->password)->toBeNull();
});

test('creates the owner as invited, not active, with no inviter', function () use ($attributes) {
    /** @noinspection PhpUnhandledExceptionInspection */
    ['owner' => $owner] = app(ProvisionOrganizationAction::class)->handle($attributes);

    expect($owner->role)->toBe(OrganizationRole::Owner)
        ->and($owner->status)->toBe(OrganizationMemberStatus::Invited)
        ->and($owner->invited_by)->toBeNull()
        ->and($owner->invitation_expires_at->isBetween(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()))->toBeTrue();
});

test('stores a hashed token that matches the plaintext token returned', function () use ($attributes) {
    /** @noinspection PhpUnhandledExceptionInspection */
    ['owner' => $owner, 'token' => $token] = app(ProvisionOrganizationAction::class)->handle($attributes);

    expect(hash('sha256', $token))->toBe($owner->invitation_token);
});

test('dispatches the invitation notification with no inviter', function () use ($attributes) {
    Notification::fake();

    /** @noinspection PhpUnhandledExceptionInspection */
    ['owner' => $owner] = app(ProvisionOrganizationAction::class)->handle($attributes);

    /** @noinspection PhpUnhandledExceptionInspection */
    Notification::assertSentTo(
        $owner,
        OrganizationInvitationNotification::class,
        fn (OrganizationInvitationNotification $notification): bool => $notification->organization->is($owner->organization)
            && $notification->invitedBy === null,
    );
});

test('rolls back the organization when the owner insert fails', function () use ($attributes) {
    User::factory()->create(['email' => $attributes['email']]);

    $attempt = function () use ($attributes): array {
        /** @noinspection PhpUnhandledExceptionInspection */
        return app(ProvisionOrganizationAction::class)->handle($attributes);
    };

    expect($attempt)->toThrow(QueryException::class)
        ->and(Organization::query()->where('name', 'Acme Insurance')->exists())->toBeFalse();
});
