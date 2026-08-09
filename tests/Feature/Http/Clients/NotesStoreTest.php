<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->post(route('clients.notes.store', $client), ['body' => 'A note.'])
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();
    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->post(route('clients.notes.store', $client), ['body' => 'A note.'])
        ->assertNotFound();
});

test('rejects a missing body', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notes.store', $client))
        ->assertInvalid(['body']);

    $this->assertDatabaseCount('notes', 0);
});

test('rejects a body exceeding the configured max length', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $body = str_repeat('a', config('notes.max_length') + 1);

    $this->actingAs($user)
        ->post(route('clients.notes.store', $client), ['body' => $body])
        ->assertInvalid(['body']);

    $this->assertDatabaseCount('notes', 0);
});

test('accepts a body exactly at the configured max length', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $body = str_repeat('a', config('notes.max_length'));

    $this->actingAs($user)
        ->post(route('clients.notes.store', $client), ['body' => $body])
        ->assertCreated();

    $this->assertDatabaseHas('notes', ['body' => $body]);
});

test('creates a note with created_by set to the acting user', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->post(route('clients.notes.store', $client), ['body' => 'Called the client about renewal.'])
        ->assertCreated()
        ->assertJson([
            'body' => 'Called the client about renewal.',
            'pinned' => false,
        ]);

    $this->assertDatabaseHas('notes', [
        'notable_type' => $client->getMorphClass(),
        'notable_id' => $client->id,
        'organization_id' => $user->organization_id,
        'created_by' => $user->id,
        'body' => 'Called the client about renewal.',
    ]);
});

test('preserves line breaks in the body', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $body = "Line one.\nLine two.\nLine three.";

    $this->actingAs($user)
        ->post(route('clients.notes.store', $client), ['body' => $body])
        ->assertCreated()
        ->assertJson(['body' => $body]);

    $this->assertDatabaseHas('notes', ['body' => $body]);
});
