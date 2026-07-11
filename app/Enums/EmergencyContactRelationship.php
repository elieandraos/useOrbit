<?php

declare(strict_types=1);

namespace App\Enums;

enum EmergencyContactRelationship: string
{
    case Spouse = 'spouse';
    case Parent = 'parent';
    case Child = 'child';
    case Sibling = 'sibling';
    case Friend = 'friend';
    case Other = 'other';

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
            self::Spouse => 'Spouse',
            self::Parent => 'Parent',
            self::Child => 'Child',
            self::Sibling => 'Sibling',
            self::Friend => 'Friend',
            self::Other => 'Other',
        };
    }
}
