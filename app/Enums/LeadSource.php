<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadSource: string
{
    case Referral = 'referral';
    case Website = 'website';
    case SocialMedia = 'social_media';
    case Partner = 'partner';
    case WalkIn = 'walk_in';
    case ColdCall = 'cold_call';

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
            self::Referral => 'Referral',
            self::Website => 'Website',
            self::SocialMedia => 'Social media',
            self::Partner => 'Partner',
            self::WalkIn => 'Walk-in',
            self::ColdCall => 'Cold call',
        };
    }
}
