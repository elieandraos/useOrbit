<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationReason;
use App\Models\Contracts\HasNotificationParent;
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
        return $this->describe($this->resource);
    }

    /** @return array{reason: string, parent: array{kind: string, slug: string, name: string}|null} */
    protected function meta(): array
    {
        return [
            'reason' => $this->reason->value,
            'parent' => $this->resource instanceof HasNotificationParent
                ? $this->describe($this->resource->notificationParent())
                : null,
        ];
    }

    protected function summary(): string
    {
        return $this->reason->summary();
    }

    /** @return array{kind: string, slug: string, name: string} */
    private function describe(Model&NotificationSubject $subject): array
    {
        return [
            'kind' => $subject->notificationSubjectKind(),
            'slug' => (string) $subject->getRouteKey(),
            'name' => $subject->notificationSubjectName(),
        ];
    }
}
