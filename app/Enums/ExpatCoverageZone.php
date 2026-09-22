<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpatCoverageZone: string
{
    case In = 'in';
    case InOut = 'in_out';

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
            self::In => 'In',
            self::InOut => 'In-Out',
        };
    }
}
