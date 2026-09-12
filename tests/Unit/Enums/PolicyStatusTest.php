<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;

test('label returns the human-readable name for each status', function () {
    expect(PolicyStatus::Active->label())->toBe('Active')
        ->and(PolicyStatus::Cancelled->label())->toBe('Cancelled')
        ->and(PolicyStatus::Frozen->label())->toBe('Frozen');
});
