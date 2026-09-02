<?php

declare(strict_types=1);

use App\Enums\CarrierStatus;
use App\Exports\CarriersExport;
use App\Models\Carrier;
use App\Models\CarrierBranch;

test('headings returns the export column labels', function () {
    $export = new CarriersExport([], null, 'asc');

    expect($export->headings())->toBe([
        'Name',
        'Phone',
        'Website',
        'Branches',
        'Clients',
        'Policies',
        'Status',
    ]);
});

test('map transforms a carrier into an export row', function () {
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->create([
        'name' => 'Alpha Assurance',
        'phone' => '+96170123456',
        'website' => 'alpha-assurance.com',
        'status' => CarrierStatus::Active->value,
    ]);
    CarrierBranch::factory()->forCarrier($carrier)->create();
    $carrier->load('branches');

    $export = new CarriersExport([], null, 'asc');

    expect($export->map($carrier))->toBe([
        'Alpha Assurance',
        '+96170123456',
        'alpha-assurance.com',
        1,
        0,
        0,
        'Active',
    ]);
});

test('map returns a zero branch count when there are no branches', function () {
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->create()->load('branches');

    $export = new CarriersExport([], null, 'asc');
    $row = $export->map($carrier);

    expect($row[3])->toBe(0);
});
