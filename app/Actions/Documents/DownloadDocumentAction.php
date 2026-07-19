<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class DownloadDocumentAction
{
    public function handle(Document $document): Response
    {
        abort_if($document->status !== DocumentStatus::Completed, 404);

        if ($document->disk === 's3') {
            return redirect()->away(
                Storage::disk($document->disk)->temporaryUrl($document->path, now()->addMinutes(5))
            );
        }

        return Storage::disk($document->disk)->download($document->path, $document->original_filename);
    }
}
