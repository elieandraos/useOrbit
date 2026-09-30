<?php

declare(strict_types=1);

use App\Enums\TravelCoverageTier;

test('label returns the human-readable name for each tier', function () {
    expect(TravelCoverageTier::Basic->label())->toBe('Basic')
        ->and(TravelCoverageTier::Standard->label())->toBe('Standard')
        ->and(TravelCoverageTier::Premium->label())->toBe('Premium');
});

test('all returns every case as a label/value pair', function () {
    expect(TravelCoverageTier::all())->toBe([
        ['label' => 'Basic', 'value' => 'basic'],
        ['label' => 'Standard', 'value' => 'standard'],
        ['label' => 'Premium', 'value' => 'premium'],
    ]);
});
