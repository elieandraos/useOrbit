<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

final class MemberRoleChangedNotification extends EnvelopeNotification
{
    public const string ACTION = 'member.role_changed';

    public function __construct(
        User $actor,
        private readonly Organization $organization,
        private readonly User $member,
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

    /** @return array{member: array{id: int, name: string, email: string}, from_role: string, to_role: string} */
    protected function meta(): array
    {
        return [
            'member' => [
                'id' => $this->member->id,
                'name' => $this->member->name,
                'email' => $this->member->email,
            ],
            'from_role' => $this->fromRole->value,
            'to_role' => $this->toRole->value,
        ];
    }

    protected function summary(): string
    {
        return __(":actor changed :member's role from :from to :to.", [
            'actor' => $this->actor?->name,
            'member' => $this->member->name,
            'from' => $this->fromRole->label(),
            'to' => $this->toRole->label(),
        ]);
    }
}
