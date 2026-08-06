<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use App\Models\User;

final class MemberJoinedNotification extends EnvelopeNotification
{
    public const string ACTION = 'member.joined';

    private readonly User $member;

    public function __construct(
        private readonly Organization $organization,
        User $member,
    ) {
        $this->member = $member;

        parent::__construct($member);
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

    /** @return array{member: array{id: int, name: string, email: string, role: string|null}} */
    protected function meta(): array
    {
        return [
            'member' => [
                'id' => $this->member->id,
                'name' => $this->member->name,
                'email' => $this->member->email,
                'role' => $this->member->organizationRole()?->value,
            ],
        ];
    }

    protected function summary(): string
    {
        return __(':name joined :organization.', [
            'name' => $this->member->name,
            'organization' => $this->organization->name,
        ]);
    }
}
