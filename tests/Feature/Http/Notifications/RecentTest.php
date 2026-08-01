<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('notifications.recent'))
        ->assertRedirect(route('login'));
});

test('member sees only their own notifications', function () {
    $user = User::factory()->withOrganization()->create();
    $otherUser = User::factory()->forOrganization($user->currentOrganization)->create();

    $mine = createNotificationFor($user);
    createNotificationFor($otherUser);

    $response = $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($mine->id);
});

test('unread notifications are ordered before read notifications regardless of recency', function () {
    $user = User::factory()->withOrganization()->create();

    $olderUnread = createNotificationFor($user);
    $olderUnread->forceFill(['created_at' => now()->subDays(2)])->save();

    $newerRead = createNotificationFor($user, read: true);
    $newerRead->forceFill(['created_at' => now()->subHour()])->save();

    $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $olderUnread->id)
        ->assertJsonPath('data.1.id', $newerRead->id);
});

test('within the same read state, notifications are ordered most recent first', function () {
    $user = User::factory()->withOrganization()->create();

    $olderUnread = createNotificationFor($user);
    $olderUnread->forceFill(['created_at' => now()->subDay()])->save();
    $newerUnread = createNotificationFor($user);

    $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $newerUnread->id)
        ->assertJsonPath('data.1.id', $olderUnread->id);
});

test('notification payload includes read state, type and data', function () {
    $user = User::factory()->withOrganization()->create();
    $notification = createNotificationFor($user, read: true);

    $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk()
        ->assertJson([
            'data' => [
                [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                ],
            ],
        ])
        ->assertJsonPath('data.0.read_at', fn (?string $readAt) => $readAt !== null);
});

test('response includes a next cursor when more notifications exist beyond the page size', function () {
    $user = User::factory()->withOrganization()->create();

    collect(range(1, 16))->each(fn () => createNotificationFor($user));

    $response = $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(15)
        ->and($response->json('next_cursor'))->not->toBeNull();
});

test('response has a null next cursor when all notifications fit on one page', function () {
    $user = User::factory()->withOrganization()->create();

    createNotificationFor($user);
    createNotificationFor($user, read: true);

    $response = $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk();

    expect($response->json('next_cursor'))->toBeNull();
});

test('cursor continuation returns the next page of results', function () {
    $user = User::factory()->withOrganization()->create();

    collect(range(1, 16))->each(function (int $i) use ($user) {
        $notification = createNotificationFor($user);
        $notification->forceFill(['created_at' => now()->subMinutes(16 - $i)])->save();
    });

    $firstPage = $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk();

    $cursor = $firstPage->json('next_cursor');
    expect($cursor)->not->toBeNull();

    $secondPage = $this->actingAs($user)
        ->get(route('notifications.recent', ['cursor' => $cursor]))
        ->assertOk();

    expect($secondPage->json('data'))->toHaveCount(1);

    $firstPageIds = collect($firstPage->json('data'))->pluck('id');
    $secondPageIds = collect($secondPage->json('data'))->pluck('id');
    expect($firstPageIds->intersect($secondPageIds))->toBeEmpty();
});

test('mixed unread and read notifications place all unread items before all read items', function () {
    $user = User::factory()->withOrganization()->create();

    $readIds = collect(range(1, 3))
        ->map(fn () => createNotificationFor($user, read: true)->id);
    $unreadIds = collect(range(1, 3))
        ->map(fn () => createNotificationFor($user)->id);

    $response = $this->actingAs($user)
        ->get(route('notifications.recent'))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids->take(3)->sort()->values())->toEqual($unreadIds->sort()->values())
        ->and($ids->skip(3)->sort()->values())->toEqual($readIds->sort()->values());
});
