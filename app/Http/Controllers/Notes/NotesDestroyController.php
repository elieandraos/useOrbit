<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notes;

use App\Actions\Notes\DeleteNoteAction;
use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class NotesDestroyController extends Controller
{
    #[Authorize('delete', 'note')]
    public function __invoke(Note $note, DeleteNoteAction $action): RedirectResponse
    {
        $action->handle($note);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note deleted.')]);

        return back();
    }
}
