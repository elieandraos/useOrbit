<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final class UploadDocumentAction
{
    public function handle(User $user, Documentable $documentable, UploadedFile $file): Document
    {
        $path = $file->store('documents-staging', 'local');

        /** @var Document $document */
        $document = $documentable->documents()->create([
            'organization_id' => $user->current_organization_id,
            'uploaded_by' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_in_bytes' => $file->getSize(),
            'status' => DocumentStatus::Pending,
        ]);

        return $document;
    }
}
