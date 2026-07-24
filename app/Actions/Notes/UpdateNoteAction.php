<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Models\Note;

final class UpdateNoteAction
{
    /**
     * @param  array{body: string, pinned?: bool}  $attributes
     */
    public function handle(Note $note, array $attributes): Note
    {
        $note->update([
            'body' => $attributes['body'],
            'pinned' => $attributes['pinned'] ?? false,
        ]);

        return $note->refresh();
    }
}
