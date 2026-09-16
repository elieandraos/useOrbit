<?php

declare(strict_types=1);

use App\Enums\MedicalClassTier;

test('label returns the human-readable name for each tier', function () {
    expect(MedicalClassTier::ClassA->label())->toBe('Class A')
        ->and(MedicalClassTier::ClassB->label())->toBe('Class B');
});

test('all returns every case as a label/value pair', function () {
    expect(MedicalClassTier::all())->toBe([
        ['label' => 'Class A', 'value' => 'class_a'],
        ['label' => 'Class B', 'value' => 'class_b'],
    ]);
});
