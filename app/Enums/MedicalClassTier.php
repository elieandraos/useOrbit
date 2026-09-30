<?php

declare(strict_types=1);

namespace App\Enums;

enum MedicalClassTier: string
{
    case ClassA = 'class_a';
    case ClassB = 'class_b';

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
            self::ClassA => 'Class A',
            self::ClassB => 'Class B',
        };
    }
}
