<?php

declare(strict_types=1);

use App\Actions\Notes\DeleteNoteAction;
use App\Models\Note;
use App\Models\User;

test('deletes the note row', function () {
    $user = User::factory()->withOrganization()->create();
    $note = Note::factory()->forOrganization($user)->createdBy($user)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteNoteAction::class)->handle($note);

    $this->assertModelMissing($note);
});
