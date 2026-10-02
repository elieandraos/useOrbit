<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * Format an amount with its currency code and two decimals, e.g. "USD 1,500.00".
     *
     * Amounts are never converted between currencies.
     */
    public static function format(string|int|float $amount, string $currencyCode): string
    {
        return $currencyCode.' '.number_format((float) $amount, 2);
    }
}
