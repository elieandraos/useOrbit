<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyClass: string
{
    case Medical = 'medical';
    case Automotive = 'automotive';
    case Expat = 'expat';
    case Fire = 'fire';
    case Life = 'life';
    case Travel = 'travel';

    public function label(): string
    {
        return match ($this) {
            self::Medical => 'Medical',
            self::Automotive => 'Automotive',
            self::Expat => 'Expat',
            self::Fire => 'Fire',
            self::Life => 'Life',
            self::Travel => 'Travel',
        };
    }

    public function detailsRelation(): string
    {
        return match ($this) {
            self::Medical => 'medicalDetails',
            self::Automotive => 'automotiveDetails',
            self::Expat => 'expatDetails',
            self::Fire => 'fireDetails',
            self::Life => 'lifeDetails',
            self::Travel => 'travelDetails',
        };
    }
}
