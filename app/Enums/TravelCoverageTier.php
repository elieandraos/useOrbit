<?php

declare(strict_types=1);

namespace App\Enums;

enum TravelCoverageTier: string
{
    case Basic = 'basic';
    case Standard = 'standard';
    case Premium = 'premium';

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
            self::Basic => 'Basic',
            self::Standard => 'Standard',
            self::Premium => 'Premium',
        };
    }
}
