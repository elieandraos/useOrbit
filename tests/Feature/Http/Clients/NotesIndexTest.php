<?php

declare(strict_types=1);

use App\Http\Resources\ClientResource;
use App\Http\Resources\NoteResource;
use App\Models\Client;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $client = Client::factory()->create();

    $this->get(route('clients.notes.index', $client))
        ->assertRedirect(route('login'));
});

test('authenticated user gets 404 for a client from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $client = Client::factory()->for($otherOrganization)->create();

    $this->actingAs($user)
        ->get(route('clients.notes.index', $client))
        ->assertNotFound();
});

test('notes are listed pinned-first then newest', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $older = Note::factory()->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $client->getMorphClass(),
        'notable_id' => $client->id,
        'created_at' => now()->subDay(),
    ]);
    $newer = Note::factory()->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $client->getMorphClass(),
        'notable_id' => $client->id,
        'created_at' => now(),
    ]);
    $pinned = Note::factory()->forOrganization($user)->createdBy($user)->pinned()->create([
        'notable_type' => $client->getMorphClass(),
        'notable_id' => $client->id,
        'created_at' => now()->subWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('clients.notes.index', $client))
        ->assertOk()
        ->assertHasResource('client', ClientResource::make($client))
        ->assertHasResource(
            'notes',
            NoteResource::collection(
                $client->notes()->with('createdBy')->orderByDesc('pinned')->latest()->orderByDesc('id')->get()
            )
        )
        ->assertInertia(fn ($page) => $page->where('notes.0.id', $pinned->id)
            ->where('notes.1.id', $newer->id)
            ->where('notes.2.id', $older->id)
        );
});

test('notes from another client are not included', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();
    $otherClient = Client::factory()->forOrganization($user)->create();

    Note::factory(2)->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $client->getMorphClass(),
        'notable_id' => $client->id,
    ]);
    Note::factory(3)->forOrganization($user)->createdBy($user)->create([
        'notable_type' => $otherClient->getMorphClass(),
        'notable_id' => $otherClient->id,
    ]);

    $this->actingAs($user)
        ->get(route('clients.notes.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('notes', 2));
});

test('shares the note config for the composer character counter', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    $this->actingAs($user)
        ->get(route('clients.notes.index', $client))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('noteConfig.max_length', config('notes.max_length')));
});
