<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;

test('provisions a new organization and its first owner', function () {
    Notification::fake();

    $this->artisan('organizations:provision', [
        'organization' => 'Acme Insurance',
        'owner-name' => 'Jane Owner',
        'owner-email' => 'jane.owner@useorbit.com',
    ])->assertSuccessful();

    $organization = Organization::query()->where('name', 'Acme Insurance')->sole();
    $owner = User::query()->where('email', 'jane.owner@useorbit.com')->sole();

    expect($owner->organization_id)->toBe($organization->id)
        ->and($owner->role)->toBe(OrganizationRole::Owner)
        ->and($owner->status)->toBe(OrganizationMemberStatus::Invited);

    /** @noinspection PhpUnhandledExceptionInspection */
    Notification::assertSentTo($owner, OrganizationInvitationNotification::class);
});

test('prints the invitation url in its output', function () {
    $this->artisan('organizations:provision', [
        'organization' => 'Acme Insurance',
        'owner-name' => 'Jane Owner',
        'owner-email' => 'jane.owner@useorbit.com',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('invitations/');
});

test('fails with no organization created when the owner email is invalid', function () {
    $this->artisan('organizations:provision', [
        'organization' => 'Acme Insurance',
        'owner-name' => 'Jane Owner',
        'owner-email' => 'not-an-email',
    ])->assertFailed();

    expect(Organization::query()->where('name', 'Acme Insurance')->exists())->toBeFalse();
});

test('fails when the owner email is already registered', function () {
    User::factory()->create(['email' => 'jane.owner@useorbit.com']);

    $this->artisan('organizations:provision', [
        'organization' => 'Acme Insurance',
        'owner-name' => 'Jane Owner',
        'owner-email' => 'jane.owner@useorbit.com',
    ])->assertFailed();

    expect(Organization::query()->where('name', 'Acme Insurance')->exists())->toBeFalse();
});
