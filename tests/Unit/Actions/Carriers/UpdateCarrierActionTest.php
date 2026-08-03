<?php

declare(strict_types=1);

use App\Actions\Carriers\UpdateCarrierAction;
use App\Models\Carrier;
use App\Models\User;

$attributes = [
    'name' => 'Beta Traders',
    'phone' => '+961 1 555 555',
    'website' => 'beta-traders.com.lb',
];

test('updates the carrier fields in the database', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateCarrierAction::class)->handle($user, $carrier, $attributes);

    /** @var Carrier $fresh */
    $fresh = $carrier->fresh();
    expect($fresh->name)->toBe('Beta Traders')
        ->and($fresh->phone)->toBe('+961 1 555 555')
        ->and($fresh->website)->toBe('beta-traders.com.lb');
});

test('sets updated_by to the user id', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateCarrierAction::class)->handle($user, $carrier, $attributes);

    /** @var Carrier $fresh */
    $fresh = $carrier->fresh();
    expect($fresh->updated_by)->toBe($user->id);
});

test('regenerates slug when name changes', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create([
        'name' => 'Bankers Assurance',
        'slug' => 'bankers-assurance',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateCarrierAction::class)->handle($user, $carrier, $attributes);

    /** @var Carrier $fresh */
    $fresh = $carrier->fresh();
    expect($fresh->slug)->toBe('beta-traders');
});

test('keeps existing slug when name does not change', function () use ($attributes) {
    $user = User::factory()->withOrganization()->create();
    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create([
        'name' => 'Beta Traders',
        'slug' => 'beta-traders',
    ]);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateCarrierAction::class)->handle($user, $carrier, $attributes);

    /** @var Carrier $fresh */
    $fresh = $carrier->fresh();
    expect($fresh->slug)->toBe('beta-traders');
});
