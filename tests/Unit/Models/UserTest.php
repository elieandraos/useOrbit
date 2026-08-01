<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\User;

test('country resolves the user\'s associated country', function () {
    $country = Country::query()->create([
        'name' => 'Lebanon',
        'iso2' => 'LB',
        'iso3' => 'LBN',
    ]);
    $user = User::factory()->create(['country_id' => $country->id]);

    expect($user->country)->toBeInstanceOf(Country::class)
        ->and($user->country->is($country))->toBeTrue();
});

test('organizationRole returns null when the user has no current organization', function () {
    $user = User::factory()->create(['current_organization_id' => null]);

    expect($user->organizationRole())->toBeNull();
});
