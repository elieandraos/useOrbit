<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Documents\UploadDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\UploadDocumentRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\TagResource;
use App\Models\Client;
use App\Models\Document;
use App\Models\Tag;
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
            ->with(['uploadedBy', 'tags'])
            ->latest()
            ->orderByDesc('id')
            ->get();

        /** @noinspection PhpUndefinedMethodInspection */
        $tags = Tag::query()
            ->withTaggableCount((new Document)->getMorphClass(), $client->getMorphClass(), $client->id)
            ->orderBy('name')
            ->get();

        return inertia('ClientDocuments/Index', [
            'client' => ClientResource::make($client),
            'documents' => DocumentResource::collection($documents),
            'tags' => TagResource::collection($tags),
            'uploadConfig' => [
                'max_size_bytes' => config('documents.max_size'),
                'allowed_extensions' => config('documents.allowed_mimes'),
                'max_files_per_batch' => config('documents.max_files_per_batch'),
            ],
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', [Document::class, 'client'])]
    public function store(UploadDocumentRequest $request, Client $client, UploadDocumentAction $action): DocumentResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->validated('file');

        $document = $action->handle($user, $client, $file);

        return DocumentResource::make($document);
    }
}
