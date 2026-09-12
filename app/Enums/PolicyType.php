<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyType: string
{
    case Single = 'single';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Group => 'Group',
        };
    }
}
