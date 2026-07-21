<?php

declare(strict_types=1);

use App\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\Models\User;

test('marks every unread notification for the user as read', function () {
    $user = User::factory()->withOrganization()->create();
    $first = createNotificationFor($user);
    $second = createNotificationFor($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(MarkAllNotificationsAsReadAction::class)->handle($user);

    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($second->fresh()->read_at)->not->toBeNull();
});

test('leaves already-read notifications untouched', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user, read: true);
    $originalReadAt = $notification->read_at;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(MarkAllNotificationsAsReadAction::class)->handle($user);

    expect($notification->fresh()->read_at->equalTo($originalReadAt))->toBeTrue();
});

test('does not mark another user\'s unread notifications as read', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->forOrganization($user->currentOrganization)->create();
    $othersNotification = createNotificationFor($otherUser);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(MarkAllNotificationsAsReadAction::class)->handle($user);

    expect($othersNotification->fresh()->read_at)->toBeNull();
});
