<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicySource: string
{
    case Owner = 'owner';
    case Client = 'client';
    case Friend = 'friend';
    case Agent = 'agent';

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
            self::Owner => 'Owner',
            self::Client => 'Client',
            self::Friend => 'Friend',
            self::Agent => 'Agent',
        };
    }
}
