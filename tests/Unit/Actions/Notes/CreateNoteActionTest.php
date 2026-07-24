<?php

declare(strict_types=1);

use App\Actions\Notes\CreateNoteAction;
use App\Models\Client;
use App\Models\User;

test('creates a note attached to the given notable', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $note = app(CreateNoteAction::class)->handle($user, $client, ['body' => 'A note.']);

    expect($note->notable_type)->toBe($client->getMorphClass())
        ->and($note->notable_id)->toBe($client->id);
});

test('scopes the note to the user current organization', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $note = app(CreateNoteAction::class)->handle($user, $client, ['body' => 'A note.']);

    expect($note->organization_id)->toBe($user->current_organization_id);
});

test('sets created_by to the user id', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $note = app(CreateNoteAction::class)->handle($user, $client, ['body' => 'A note.']);

    expect($note->created_by)->toBe($user->id);
});

test('defaults pinned to false when not given', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $note = app(CreateNoteAction::class)->handle($user, $client, ['body' => 'A note.']);

    expect($note->pinned)->toBeFalse();
});

test('sets pinned when given', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $note = app(CreateNoteAction::class)->handle($user, $client, ['body' => 'A note.', 'pinned' => true]);

    expect($note->pinned)->toBeTrue();
});

test('stores the body as given', function () {
    $user = User::factory()->withOrganization()->create();
    $client = Client::factory()->forOrganization($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $note = app(CreateNoteAction::class)->handle($user, $client, ['body' => 'Called the client about renewal.']);

    expect($note->body)->toBe('Called the client about renewal.');
});
