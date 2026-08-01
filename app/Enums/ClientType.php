<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientType: string
{
    case Individual = 'individual';
    case Company = 'company';

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
            self::Individual => 'Individual',
            self::Company => 'Company',
        };
    }
}
