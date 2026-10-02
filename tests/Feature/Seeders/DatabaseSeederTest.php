<?php

declare(strict_types=1);

use App\Models\Currency;

test('seeding outside the local environment fills exactly the supported currencies', function () {
    $this->seed();

    expect(Currency::query()->orderBy('code')->pluck('code')->all())->toBe([
        'AED', 'AUD', 'CAD', 'CHF', 'EGP', 'EUR', 'GBP', 'LBP', 'QAR', 'SAR', 'USD',
    ]);
});
