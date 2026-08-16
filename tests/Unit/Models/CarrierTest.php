<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\User;

test('createdBy resolves the user who created the carrier', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create(['created_by' => $user->id]);

    expect($carrier->createdBy)->toBeInstanceOf(User::class)
        ->and($carrier->createdBy->is($user))->toBeTrue();
});
