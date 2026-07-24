<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Notes\CreateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\NoteResource;
use App\Models\Client;
use App\Models\Note;
use App\Models\User;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class ClientNotesController extends Controller
{
    #[Authorize('viewAny', [Note::class, 'client'])]
    public function index(Client $client): Response
    {
        $notes = $client->notes()
            ->with('createdBy')
            ->orderByDesc('pinned')
            ->latest()
            ->get();

        return inertia('ClientNotes/Index', [
            'client' => ClientResource::make($client),
            'notes' => NoteResource::collection($notes),
            'noteConfig' => [
                'max_length' => config('notes.max_length'),
            ],
        ]);
    }

    #[Authorize('create', [Note::class, 'client'])]
    public function store(StoreNoteRequest $request, Client $client, CreateNoteAction $action): NoteResource
    {
        /** @var User $user */
        $user = $request->user();

        $note = $action->handle($user, $client, $request->validated());
        $note->loadMissing('createdBy');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note created.')]);

        return NoteResource::make($note);
    }
}
