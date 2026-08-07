<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function isPrivileged(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function invitableOptions(): array
    {
        return array_map(fn (self $case) => [
            'label' => $case->label(),
            'value' => $case->value,
        ], [self::Admin, self::Member]);
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }
}
