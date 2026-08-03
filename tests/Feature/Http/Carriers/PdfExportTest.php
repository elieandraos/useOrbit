<?php

declare(strict_types=1);

use App\Models\Carrier;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $carrier = Carrier::factory()->create();

    $this->get(route('carriers.export-pdf', $carrier))
        ->assertRedirect(route('login'));
});

test('authenticated user can export a carrier from their organization to pdf', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Carrier $carrier */
    $carrier = Carrier::factory()->forOrganization($user)->create();

    $response = $this->actingAs($user)
        ->get(route('carriers.export-pdf', $carrier))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$carrier->slug.pdf");
});

test('authenticated user gets 404 for a carrier from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $carrier = Carrier::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('carriers.export-pdf', $carrier))
        ->assertNotFound();
});
