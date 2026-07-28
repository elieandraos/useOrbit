<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class DeleteDocumentAction
{
    public function handle(Document $document): void
    {
        $documentId = $document->id;
        $disk = $document->disk;
        $path = $document->path;

        $document->tags()->detach();
        $document->delete();

        if (! Storage::disk($disk)->delete($path)) {
            Log::warning('Failed to delete document file after removing its database row.', [
                'document_id' => $documentId,
                'disk' => $disk,
                'path' => $path,
            ]);
        }
    }
}
