<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Notes\CreateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Http\Resources\NoteResource;
use App\Http\Resources\PolicyResource;
use App\Models\Note;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PolicyNotesController extends Controller
{
    #[Authorize('viewAny', [Note::class, 'policy'])]
    public function index(Policy $policy): Response
    {
        $notes = $policy->notes()
            ->with('createdBy')
            ->orderByDesc('pinned')
            ->latest()
            ->orderByDesc('id')
            ->get();

        return inertia('PolicyNotes/Index', [
            'policy' => PolicyResource::make($policy->load(['client', 'carrier', 'agent'])),
            'notes' => NoteResource::collection($notes),
            'noteConfig' => [
                'max_length' => config('notes.max_length'),
            ],
        ]);
    }

    #[Authorize('create', [Note::class, 'policy'])]
    public function store(StoreNoteRequest $request, Policy $policy, CreateNoteAction $action): NoteResource
    {
        /** @var User $user */
        $user = $request->user();

        $note = $action->handle($user, $policy, $request->validated());
        $note->loadMissing('createdBy');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note created.')]);

        return NoteResource::make($note);
    }
}
