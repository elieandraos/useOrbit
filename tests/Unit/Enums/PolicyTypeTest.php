<?php

declare(strict_types=1);

use App\Enums\PolicyType;

test('label returns the human-readable name for each type', function () {
    expect(PolicyType::Single->label())->toBe('Single')
        ->and(PolicyType::Group->label())->toBe('Group');
});

test('all returns every case as a label/value pair', function () {
    expect(PolicyType::all())->toBe([
        ['label' => 'Single', 'value' => 'single'],
        ['label' => 'Group', 'value' => 'group'],
    ]);
});
