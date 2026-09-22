<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Documents\UploadDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\UploadDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\PolicyMedicalResource;
use App\Http\Resources\TagResource;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class PolicyDocumentsController extends Controller
{
    #[Authorize('viewAny', [Document::class, 'policy'])]
    public function index(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent']);

        $documents = $policy->documents()
            ->with(['uploadedBy', 'tags'])
            ->latest()
            ->orderByDesc('id')
            ->get();

        /** @noinspection PhpUndefinedMethodInspection */
        $tags = Tag::query()
            ->withDocumentCount($policy->getMorphClass(), $policy->id)
            ->orderBy('name')
            ->get();

        return inertia('PolicyDocuments/Index', [
            'policy' => PolicyMedicalResource::make($policy),
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
    #[Authorize('create', [Document::class, 'policy'])]
    public function store(UploadDocumentRequest $request, Policy $policy, UploadDocumentAction $action): DocumentResource
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->validated('file');

        $document = $action->handle($user, $policy, $file);

        return DocumentResource::make($document);
    }
}
