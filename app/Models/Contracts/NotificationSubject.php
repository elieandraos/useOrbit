<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface NotificationSubject
{
    public function notificationSubjectKind(): string;

    public function notificationSubjectName(): string;
}
