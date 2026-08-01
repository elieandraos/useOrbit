<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

test('forbidden response renders the Inertia error page', function () {
    config(['app.debug' => false]);

    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->patch(route('clients.archive', $client))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 403)
        );
});

test('not found response renders the Inertia error page', function () {
    config(['app.debug' => false]);

    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('clients.show', 'no-such-slug'))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
});

test('debug mode falls back to the default exception response', function () {
    config(['app.debug' => true]);

    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $response = $this->actingAs($user)
        ->patch(route('clients.archive', $client))
        ->assertForbidden();

    expect($response->headers->get('X-Inertia'))->toBeNull();
});
