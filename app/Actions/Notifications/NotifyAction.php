<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationReason;
use App\Models\Contracts\NotificationSubject;
use App\Models\User;
use App\Notifications\ResourceMessageNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

final class NotifyAction
{
    /**
     * @param  array<int, int>  $recipientIds
     */
    public function handle(User $actor, Model&NotificationSubject $subject, array $recipientIds, NotificationReason $reason): void
    {
        $recipients = User::query()
            ->activeInCurrentOrganization()
            ->whereKey($recipientIds)
            ->whereKeyNot($actor->id)
            ->get();

        Notification::send(
            $recipients,
            new ResourceMessageNotification($actor, $subject, $reason)->afterCommit(),
        );
    }
}
