<?php

declare(strict_types=1);

use App\Support\Money;

test('format prefixes the currency code and shows two decimals with thousands separators', function (string $amount, string $currencyCode, string $expected) {
    expect(Money::format($amount, $currencyCode))->toBe($expected);
})->with([
    'usd' => ['1500', 'USD', 'USD 1,500.00'],
    'lbp' => ['150000.00', 'LBP', 'LBP 150,000.00'],
    'zero' => ['0.00', 'USD', 'USD 0.00'],
    'cents' => ['0.5', 'EUR', 'EUR 0.50'],
    'largest stored amount' => ['9999999999999.99', 'USD', 'USD 9,999,999,999,999.99'],
]);
