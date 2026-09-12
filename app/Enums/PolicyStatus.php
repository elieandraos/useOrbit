<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Frozen = 'frozen';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Cancelled => 'Cancelled',
            self::Frozen => 'Frozen',
        };
    }
}
