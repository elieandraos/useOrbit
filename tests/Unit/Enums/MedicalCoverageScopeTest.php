<?php

declare(strict_types=1);

use App\Enums\MedicalCoverageScope;

test('label returns the human-readable name for each scope', function () {
    expect(MedicalCoverageScope::In->label())->toBe('In')
        ->and(MedicalCoverageScope::InOut->label())->toBe('In-Out');
});

test('all returns every case as a label/value pair', function () {
    expect(MedicalCoverageScope::all())->toBe([
        ['label' => 'In', 'value' => 'in'],
        ['label' => 'In-Out', 'value' => 'in_out'],
    ]);
});
