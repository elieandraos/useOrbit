<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\NoteResource;
use App\Models\Client;
use App\Models\Note;
use Illuminate\Routing\Attributes\Controllers\Authorize;
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
        ]);
    }
}
