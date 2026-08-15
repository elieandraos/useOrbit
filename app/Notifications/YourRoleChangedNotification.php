<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

final class YourRoleChangedNotification extends EnvelopeNotification
{
    public const string ACTION = 'member.your_role_changed';

    public function __construct(
        User $actor,
        private readonly Organization $organization,
        private readonly OrganizationRole $fromRole,
        private readonly OrganizationRole $toRole,
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
            'kind' => 'organization',
            'slug' => (string) $this->organization->getRouteKey(),
            'name' => $this->organization->name,
        ];
    }

    /** @return array{from_role: string, to_role: string} */
    protected function meta(): array
    {
        return [
            'from_role' => $this->fromRole->value,
            'to_role' => $this->toRole->value,
        ];
    }

    protected function summary(): string
    {
        return __('Your role was changed to :role.', ['role' => $this->toRole->label()]);
    }
}
