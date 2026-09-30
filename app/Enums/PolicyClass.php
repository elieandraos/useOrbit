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
            self::Medical => 'Medical',
            self::Automotive => 'Automotive',
            self::Expat => 'Expat',
            self::Fire => 'Fire',
            self::Life => 'Life',
            self::Travel => 'Travel',
        };
    }

    /**
     * Get the canonical subclasses this class accepts, spelled exactly as they are stored.
     *
     * @return list<string>
     */
    public function subclasses(): array
    {
        return match ($this) {
            self::Medical => ['Hospitalization', 'Outpatient', 'Dental', 'Vision', 'Major medical'],
            self::Automotive => ['Third Party Liability', 'All Risk', 'Compulsory'],
            self::Expat => ['Worldwide', 'Schengen', 'GCC', 'Student'],
            self::Life => ['Term', 'Whole life', 'Endowment', 'Group life'],
            self::Fire => ['Building', 'Contents', 'Business interruption', 'All risk'],
            self::Travel => ['Schengen', 'Worldwide', 'Student', 'Pilgrim'],
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
