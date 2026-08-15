<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use App\Models\User;

final class MemberRemovedNotification extends EnvelopeNotification
{
    public const string ACTION = 'member.removed';

    /**
     * @param  array{id: int, name: string, email: string, role: string}  $member
     * @param  array{id: int, name: string}  $successor
     */
    public function __construct(
        User $actor,
        private readonly Organization $organization,
        private readonly array $member,
        private readonly array $successor,
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

    /**
     * @return array{
     *     member: array{id: int, name: string, email: string, role: string},
     *     successor: array{id: int, name: string},
     * }
     */
    protected function meta(): array
    {
        return [
            'member' => $this->member,
            'successor' => $this->successor,
        ];
    }

    protected function summary(): string
    {
        return __(':actor removed :member from :organization.', [
            'actor' => $this->actor?->name,
            'member' => $this->member['name'],
            'organization' => $this->organization->name,
        ]);
    }
}
