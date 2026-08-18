<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use App\Models\User;

final class YourTwoFactorAuthenticationWasResetNotification extends EnvelopeNotification
{
    public const string ACTION = 'member.your_two_factor_reset';

    public function __construct(
        User $actor,
        private readonly Organization $organization,
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

    /** @return array{} */
    protected function meta(): array
    {
        return [];
    }

    protected function summary(): string
    {
        return __(':actor reset your two-factor authentication.', [
            'actor' => $this->actor?->name,
        ]);
    }
}
