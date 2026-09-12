<?php

declare(strict_types=1);

use App\Enums\PolicySource;

test('label returns the human-readable name for each source', function () {
    expect(PolicySource::Owner->label())->toBe('Owner')
        ->and(PolicySource::Client->label())->toBe('Client')
        ->and(PolicySource::Friend->label())->toBe('Friend')
        ->and(PolicySource::Agent->label())->toBe('Agent');
});
