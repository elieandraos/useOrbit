<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

final class UpdateLastLoginTimestamp
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->last_login_at = now();
        $user->saveQuietly();
    }
}
