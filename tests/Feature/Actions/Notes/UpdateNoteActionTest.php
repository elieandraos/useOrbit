<?php

declare(strict_types=1);

use App\Actions\Notes\UpdateNoteAction;
use App\Models\Note;
use App\Models\User;

test('updates the note body', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create(['body' => 'Original.']);

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateNoteAction::class)->handle($note, ['body' => 'Updated.']);

    expect($updated->body)->toBe('Updated.');
});

test('defaults pinned to false when not given', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->pinned()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateNoteAction::class)->handle($note, ['body' => 'Updated.']);

    expect($updated->pinned)->toBeFalse();
});

test('sets pinned when given', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create(['pinned' => false]);

    /** @noinspection PhpUnhandledExceptionInspection */
    $updated = app(UpdateNoteAction::class)->handle($note, ['body' => 'Updated.', 'pinned' => true]);

    expect($updated->pinned)->toBeTrue();
});

test('persists the update to the database', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create(['body' => 'Original.']);

    /** @noinspection PhpUnhandledExceptionInspection */
    app(UpdateNoteAction::class)->handle($note, ['body' => 'Updated.']);

    expect($note->fresh()->body)->toBe('Updated.');
});
