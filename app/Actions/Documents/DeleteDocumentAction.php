<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

final class DeleteDocumentAction
{
    public function handle(Document $document): void
    {
        $document->delete();

        Storage::disk($document->disk)->delete($document->path);
    }
}
