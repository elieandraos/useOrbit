<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user);

    $this->post(route('notifications.read', $notification))
        ->assertRedirect(route('login'));
});

test('member can mark their own notification as read', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user);

    $this->actingAs($user)
        ->post(route('notifications.read', $notification))
        ->assertNoContent();
});

test('member cannot mark another user\'s notification as read', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->forOrganization($user->currentOrganization)->create();
    $othersNotification = createNotificationFor($otherUser);

    $this->actingAs($user)
        ->post(route('notifications.read', $othersNotification))
        ->assertNotFound();
});
