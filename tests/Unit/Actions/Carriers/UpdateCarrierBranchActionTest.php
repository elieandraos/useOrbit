<?php

declare(strict_types=1);

use App\Actions\Carriers\UpdateCarrierBranchAction;
use App\Models\CarrierBranch;

$attributes = [
    'street' => 'Boulevard Riad El Solh',
    'building_floor' => 'Azar Bldg, 3rd Fl',
    'city' => 'Tripoli',
    'contact_name' => 'Rami Haddad',
    'contact_role' => 'Regional Manager',
    'contact_email' => 'rami.haddad@bankers.com.lb',
    'contact_phone' => '+961 3 162 408',
];

test('updates the branch fields in the database', function () use ($attributes) {
    $branch = CarrierBranch::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateCarrierBranchAction::class)->handle($branch, $attributes);

    expect($updated->street)->toBe('Boulevard Riad El Solh')
        ->and($updated->building_floor)->toBe('Azar Bldg, 3rd Fl')
        ->and($updated->city)->toBe('Tripoli')
        ->and($updated->contact_name)->toBe('Rami Haddad')
        ->and($updated->contact_role)->toBe('Regional Manager')
        ->and($updated->contact_email)->toBe('rami.haddad@bankers.com.lb')
        ->and($updated->contact_phone)->toBe('+961 3 162 408');
});

test('does not affect the branch carrier_id', function () use ($attributes) {
    $branch = CarrierBranch::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateCarrierBranchAction::class)->handle($branch, $attributes);

    expect($updated->carrier_id)->toBe($branch->carrier_id);
});
