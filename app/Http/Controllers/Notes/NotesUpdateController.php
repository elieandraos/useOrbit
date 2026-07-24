<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notes;

use App\Actions\Notes\UpdateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class NotesUpdateController extends Controller
{
    #[Authorize('update', 'note')]
    public function __invoke(UpdateNoteRequest $request, Note $note, UpdateNoteAction $action): NoteResource
    {
        $note = $action->handle($note, $request->validated());
        $note->loadMissing('createdBy');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note updated.')]);

        return NoteResource::make($note);
    }
}
