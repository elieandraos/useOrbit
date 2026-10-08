<?php

declare(strict_types=1);

use App\Enums\PolicyDisplayStatus;

test('all returns every display status as a label/value pair', function () {
    expect(PolicyDisplayStatus::all())->toBe([
        ['label' => 'Upcoming', 'value' => 'upcoming'],
        ['label' => 'In force', 'value' => 'in_force'],
        ['label' => 'Expired', 'value' => 'expired'],
        ['label' => 'Cancelled', 'value' => 'cancelled'],
        ['label' => 'Frozen', 'value' => 'frozen'],
    ]);
});
