<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyType: string
{
    case Single = 'single';
    case Group = 'group';

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => [
            'label' => $case->label(),
            'value' => $case->value,
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Group => 'Group',
        };
    }
}
