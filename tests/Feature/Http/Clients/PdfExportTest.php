<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.export-pdf', $client))
        ->assertRedirect(route('login'));
});

test('authenticated user can export a client from their organization to pdf', function () {
    $user = User::factory()->withOrganization()->create();

    /** @var Client $client */
    $client = Client::factory()->create(['organization_id' => $user->current_organization_id]);

    $response = $this->actingAs($user)
        ->get(route('clients.export-pdf', $client))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain("$client->slug.pdf");
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('clients.export-pdf', $client))
        ->assertNotFound();
});
