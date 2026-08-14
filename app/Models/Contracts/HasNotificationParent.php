<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasNotificationParent
{
    public function notificationParent(): Model&NotificationSubject;
}
