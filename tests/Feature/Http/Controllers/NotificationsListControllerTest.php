<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('notifications.recent'))
        ->assertRedirect(route('login'));
});

test('member sees only their own notifications, most recent first', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->forOrganization($user->currentOrganization)->create();

    $older = createNotificationFor($user);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    $newer = createNotificationFor($user);
    createNotificationFor($otherUser);

    $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk()
        ->assertJson([
            ['id' => $newer->id],
            ['id' => $older->id],
        ])
        ->assertJsonCount(2);
});

test('notification payload includes read state and data', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user, read: true);

    $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk()
        ->assertJson([
            [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
            ],
        ])
        ->assertJsonPath('0.read_at', fn (?string $readAt) => $readAt !== null);
});
