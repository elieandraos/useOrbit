<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\StoreDocumentJob;
use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentsUploadBatchProcessed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

final class FinalizeDocumentsUploadBatchAction
{
    /**
     * @param  array<int, int>  $documentIds
     * @return int the number of submitted IDs that were rejected (not owned by the user, wrong org, or no longer pending)
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $documentIds): int
    {
        $documents = Document::query()
            ->whereKey($documentIds)
            ->where('organization_id', $user->organization_id)
            ->where('uploaded_by', $user->id)
            ->where('status', DocumentStatus::Pending)
            ->with('documentable')
            ->get();

        $rejectedCount = count(array_unique($documentIds)) - $documents->count();

        if ($documents->isEmpty()) {
            return $rejectedCount;
        }

        $ids = $documents->pluck('id');

        /** @var Document $firstDocument */
        $firstDocument = $documents->first();

        /** @var Model&Documentable $documentable */
        $documentable = $firstDocument->documentable;

        Bus::batch($documents->map(fn (Document $document): StoreDocumentJob => new StoreDocumentJob($document))->all())
            ->finally(function () use ($user, $ids, $documentable): void {
                $outcome = app(CountDocumentsUploadBatchOutcomeAction::class)->handle($ids);
                $processedDocuments = Document::query()->whereKey($ids)->get(['id', 'status']);

                $user->notify(new DocumentsUploadBatchProcessed($outcome, $processedDocuments, $documentable));
            })
            ->dispatch();

        return $rejectedCount;
    }
}
