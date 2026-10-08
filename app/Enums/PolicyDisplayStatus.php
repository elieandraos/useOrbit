<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * The status users see and filter on, computed from the stored status and the policy dates.
 *
 * Never persisted. The SQL counterpart of resolve() is PolicyFilter::status(), which must keep the same boundaries.
 */
enum PolicyDisplayStatus: string
{
    case Upcoming = 'upcoming';
    case InForce = 'in_force';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Frozen = 'frozen';

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

    /**
     * A stored Cancelled or Frozen wins; a stored Active follows the organization-local date, both dates inclusive.
     *
     * Dates are compared as calendar dates, so the timezone each value carries never shifts the boundary.
     */
    public static function resolve(PolicyStatus $status, CarbonInterface $effectiveDate, CarbonInterface $expiryDate, CarbonInterface $today): self
    {
        $todayDate = $today->toDateString();

        return match (true) {
            $status === PolicyStatus::Cancelled => self::Cancelled,
            $status === PolicyStatus::Frozen => self::Frozen,
            $todayDate < $effectiveDate->toDateString() => self::Upcoming,
            $todayDate > $expiryDate->toDateString() => self::Expired,
            default => self::InForce,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::InForce => 'In force',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
            self::Frozen => 'Frozen',
        };
    }
}
