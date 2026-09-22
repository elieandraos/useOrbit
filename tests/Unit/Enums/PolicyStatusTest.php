<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;

test('label returns the human-readable name for each status', function () {
    expect(PolicyStatus::Active->label())->toBe('Active')
        ->and(PolicyStatus::Cancelled->label())->toBe('Cancelled')
        ->and(PolicyStatus::Frozen->label())->toBe('Frozen');
});

test('all returns every case as a label/value pair', function () {
    expect(PolicyStatus::all())->toBe([
        ['label' => 'Active', 'value' => 'active'],
        ['label' => 'Cancelled', 'value' => 'cancelled'],
        ['label' => 'Frozen', 'value' => 'frozen'],
    ]);
});
