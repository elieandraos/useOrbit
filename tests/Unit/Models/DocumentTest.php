<?php

declare(strict_types=1);

use App\Models\Document;

test('ownerColumn returns the documentable_type column', function () {
    expect(Document::ownerColumn())->toBe('documentable_type');
});
