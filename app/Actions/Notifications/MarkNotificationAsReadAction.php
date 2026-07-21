<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

final class MarkNotificationAsReadAction
{
    public function handle(User $user, string $notificationId): void
    {
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->findOrFail($notificationId);

        $notification->markAsRead();
    }
}
