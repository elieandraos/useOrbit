<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\StoreDocumentJob;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentsUploadBatchProcessed;
use Illuminate\Support\Facades\Bus;

final class FinalizeDocumentsUploadBatchAction
{
    /**
     * @param  array<int, int>  $documentIds
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $documentIds): void
    {
        $documents = Document::query()
            ->whereKey($documentIds)
            ->where('organization_id', $user->current_organization_id)
            ->where('uploaded_by', $user->id)
            ->where('status', DocumentStatus::Pending)
            ->with('documentable')
            ->get();

        if ($documents->isEmpty()) {
            return;
        }

        $ids = $documents->pluck('id');

        /** @var Document $firstDocument */
        $firstDocument = $documents->first();

        /** @var Client $client */
        $client = $firstDocument->documentable;

        Bus::batch($documents->map(fn (Document $document): StoreDocumentJob => new StoreDocumentJob($document))->all())
            ->finally(function () use ($user, $ids, $client): void {
                $outcome = app(CountDocumentsUploadBatchOutcomeAction::class)->handle($ids);
                $processedDocuments = Document::query()->whereKey($ids)->get(['id', 'status']);

                $user->notify(new DocumentsUploadBatchProcessed($outcome, $processedDocuments, $client));
            })
            ->dispatch();
    }
}
