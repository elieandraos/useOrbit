<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Frozen = 'frozen';

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
            self::Active => 'Active',
            self::Cancelled => 'Cancelled',
            self::Frozen => 'Frozen',
        };
    }
}
