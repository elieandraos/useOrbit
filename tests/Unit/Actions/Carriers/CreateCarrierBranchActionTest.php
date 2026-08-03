<?php

declare(strict_types=1);

use App\Actions\Carriers\CreateCarrierBranchAction;
use App\Models\Carrier;
use App\Models\CarrierBranch;

$attributes = [
    'street' => 'Hamra Street',
    'building_floor' => '4th Floor',
    'city' => 'Tripoli',
    'contact_name' => 'Nadine Fares',
    'contact_role' => 'Branch Manager',
    'contact_email' => 'nadine.fares@bankers.com.lb',
    'contact_phone' => '+961 3 555 111',
];

test('creates a branch under the given carrier with the submitted fields', function () use ($attributes) {
    $carrier = Carrier::factory()->create();

    $branch = app(CreateCarrierBranchAction::class)->handle($carrier, $attributes);

    expect($branch->carrier_id)->toBe($carrier->id)
        ->and($branch->street)->toBe('Hamra Street')
        ->and($branch->building_floor)->toBe('4th Floor')
        ->and($branch->city)->toBe('Tripoli')
        ->and($branch->contact_name)->toBe('Nadine Fares')
        ->and($branch->contact_role)->toBe('Branch Manager')
        ->and($branch->contact_email)->toBe('nadine.fares@bankers.com.lb')
        ->and($branch->contact_phone)->toBe('+961 3 555 111');
});

test('adds an additional branch without touching existing branches', function () use ($attributes) {
    $carrier = Carrier::factory()->create();
    $existingBranch = CarrierBranch::factory()->forCarrier($carrier)->create();

    app(CreateCarrierBranchAction::class)->handle($carrier, $attributes);

    expect($carrier->fresh()->branches)->toHaveCount(2)
        ->and($existingBranch->fresh()->city)->toBe($existingBranch->city);
});
