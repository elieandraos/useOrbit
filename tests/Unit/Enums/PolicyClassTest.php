<?php

declare(strict_types=1);

use App\Enums\PolicyClass;

test('label returns the human-readable name for each class', function () {
    expect(PolicyClass::Medical->label())->toBe('Medical')
        ->and(PolicyClass::Automotive->label())->toBe('Automotive')
        ->and(PolicyClass::Expat->label())->toBe('Expat')
        ->and(PolicyClass::Fire->label())->toBe('Fire')
        ->and(PolicyClass::Life->label())->toBe('Life')
        ->and(PolicyClass::Travel->label())->toBe('Travel');
});
