<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Documents\UploadDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\UploadDocumentRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\DocumentResource;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
            ->get();

        return inertia('Documents/Index', [
            'client' => ClientResource::make($client),
            'documents' => DocumentResource::collection($documents),
            'uploadConfig' => [
                'max_size_bytes' => config('documents.max_size'),
                'allowed_extensions' => collect(config('documents.allowed_mimes'))
                    ->map(fn (string $mime) => strtoupper($mime))
                    ->values()
                    ->all(),
                'max_files_per_batch' => config('documents.max_files_per_batch'),
            ],
        ]);
    }

    #[Authorize('create', [Document::class, 'client'])]
    public function store(UploadDocumentRequest $request, Client $client, UploadDocumentAction $action): DocumentResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->validated('file');

        $document = $action->handle($user, $client, $file);
        $document->loadMissing('uploadedBy');

        return DocumentResource::make($document);
    }
}
