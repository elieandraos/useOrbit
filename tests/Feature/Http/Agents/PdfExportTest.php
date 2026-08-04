<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $agent = Agent::factory()->create();

    $this->get(route('agents.export-pdf', $agent))
        ->assertRedirect(route('login'));
});

test('authenticated user can export an agent from their organization to pdf', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Agent $agent */
    $agent = Agent::factory()->forOrganization($user)->create();

    $response = $this->actingAs($user)
        ->get(route('agents.export-pdf', $agent))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$agent->slug.pdf");
});

test('authenticated user gets 404 for an agent from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $agent = Agent::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('agents.export-pdf', $agent))
        ->assertNotFound();
});
