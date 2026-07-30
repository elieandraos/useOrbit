<?php

declare(strict_types=1);

use App\Models\Document;

test('ownerColumn returns the documentable_type column', function () {
    expect(Document::ownerColumn())->toBe('documentable_type');
});

test('ownerIdColumn returns the documentable_id column', function () {
    expect(Document::ownerIdColumn())->toBe('documentable_id');
});
