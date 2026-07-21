<?php

declare(strict_types=1);

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->post(route('notifications.read-all'))
        ->assertRedirect(route('login'));
});

test('member can mark all their notifications as read', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirectBack();
});
