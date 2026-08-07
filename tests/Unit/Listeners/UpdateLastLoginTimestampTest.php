<?php

declare(strict_types=1);

use App\Listeners\UpdateLastLoginTimestamp;
use App\Models\User;
use Illuminate\Auth\Events\Login;

test('sets last_login_at on the authenticated user', function () {
    $user = User::factory()->create(['last_login_at' => null]);

    app(UpdateLastLoginTimestamp::class)->handle(new Login('web', $user, false));

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
