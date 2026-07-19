<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\DocumentResource;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class ClientDocumentsController extends Controller
{
    #[Authorize('viewAny', [Document::class, 'client'])]
    public function index(Client $client): Response
    {
        $documents = $client->documents()
            ->with('uploadedBy')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('Documents/Index', [
            'client' => ClientResource::make($client),
            'documents' => DocumentResource::collection($documents),
        ]);
    }
}
