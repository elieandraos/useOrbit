<?php

declare(strict_types=1);

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

test('guests are redirected to the login page', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

test('member sees only their own notifications, paginated most recent first', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->forOrganization($user->currentOrganization)->create();

    $older = createNotificationFor($user);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    $newer = createNotificationFor($user);
    createNotificationFor($otherUser);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertHasPaginatedResource(
            'notifications',
            NotificationResource::collection(
                DatabaseNotification::query()
                    ->where('notifiable_id', $user->id)
                    ->latest()
                    ->paginate(10)
            )
        )
        ->assertInertia(fn ($page) => $page
            ->where('notifications.data.0.id', $newer->id)
            ->where('notifications.data.1.id', $older->id)
        );
});

test('notifications are paginated', function () {
    $user = User::factory()->withOrganization()->create();

    collect(range(1, 17))->each(fn () => createNotificationFor($user));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notifications.meta.total', 17)
            ->where('notifications.meta.per_page', 10)
            ->has('notifications.data', 10)
        );
});
