<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationReason;
use App\Models\Contracts\NotificationSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ResourceMessageNotification extends EnvelopeNotification
{
    public const string ACTION = 'resource.message';

    public function __construct(
        User $actor,
        private readonly Model&NotificationSubject $resource,
        private readonly NotificationReason $reason,
    ) {
        parent::__construct($actor);
    }

    protected function action(): string
    {
        return self::ACTION;
    }

    /** @return array{kind: string, slug: string, name: string} */
    protected function subject(): array
    {
        return [
            'kind' => $this->resource->notificationSubjectKind(),
            'slug' => (string) $this->resource->getRouteKey(),
            'name' => $this->resource->notificationSubjectName(),
        ];
    }

    /** @return array{reason: string} */
    protected function meta(): array
    {
        return [
            'reason' => $this->reason->value,
        ];
    }

    protected function summary(): string
    {
        return $this->reason->summary();
    }
}
