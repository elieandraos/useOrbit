<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Contracts\NotificationSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ResourceArchivedNotification extends EnvelopeNotification
{
    public const string ACTION = 'resource.archived';

    public function __construct(
        User $actor,
        private readonly Model&NotificationSubject $resource,
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

    /** @return array{} */
    protected function meta(): array
    {
        return [];
    }

    protected function summary(): string
    {
        return __(':actor archived :kind :name.', [
            'actor' => $this->actor?->name,
            'kind' => $this->resource->notificationSubjectKind(),
            'name' => $this->resource->notificationSubjectName(),
        ]);
    }
}
