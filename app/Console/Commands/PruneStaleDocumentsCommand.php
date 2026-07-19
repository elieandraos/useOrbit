<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('documents:prune-stale')]
#[Description('Delete stale pending documents and their staging files.')]
final class PruneStaleDocumentsCommand extends Command
{
    public function handle(): void
    {
        Document::query()
            ->where('status', DocumentStatus::Pending)
            ->where('created_at', '<', now()->subHours(config('documents.prune_after_hours')))
            ->lazyById()
            ->each(function (Document $document): void {
                Storage::disk($document->disk)->delete($document->path);
                $document->delete();
            });
    }
}
