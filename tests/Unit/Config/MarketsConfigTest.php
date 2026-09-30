<?php

declare(strict_types=1);

/**
 * @return array{countries: array<int, string>}
 */
function loadMarketsConfig(string $marketCountries): array
{
    $_SERVER['MARKET_COUNTRIES'] = $marketCountries;

    try {
        return require __DIR__.'/../../../config/markets.php';
    } finally {
        unset($_SERVER['MARKET_COUNTRIES']);
    }
}

test('market countries are trimmed, uppercased and stripped of blanks', function () {
    expect(loadMarketsConfig(' lb, Ae ,,sa ')['countries'])->toBe(['LB', 'AE', 'SA']);
});

test('an empty value configures no market', function () {
    expect(loadMarketsConfig('')['countries'])->toBe([]);
});
