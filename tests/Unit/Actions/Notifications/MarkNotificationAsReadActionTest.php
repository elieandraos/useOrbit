<?php

declare(strict_types=1);

use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('marks the given notification as read', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(MarkNotificationAsReadAction::class)->handle($user, $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('leaves an already-read notification as is', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user, read: true);
    $originalReadAt = $notification->read_at;

    /** @noinspection PhpUnhandledExceptionInspection */
    app(MarkNotificationAsReadAction::class)->handle($user, $notification->id);

    expect($notification->fresh()->read_at->equalTo($originalReadAt))->toBeTrue();
});

test('throws when the notification belongs to another user', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->forOrganization($user->currentOrganization)->create();
    $othersNotification = createNotificationFor($otherUser);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(MarkNotificationAsReadAction::class)->handle($user, $othersNotification->id);
})->throws(ModelNotFoundException::class);
