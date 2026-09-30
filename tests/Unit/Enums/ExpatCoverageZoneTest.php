<?php

declare(strict_types=1);

use App\Enums\ExpatCoverageZone;

test('label returns the human-readable name for each zone', function () {
    expect(ExpatCoverageZone::In->label())->toBe('In')
        ->and(ExpatCoverageZone::InOut->label())->toBe('In-Out');
});

test('all returns every case as a label/value pair', function () {
    expect(ExpatCoverageZone::all())->toBe([
        ['label' => 'In', 'value' => 'in'],
        ['label' => 'In-Out', 'value' => 'in_out'],
    ]);
});
