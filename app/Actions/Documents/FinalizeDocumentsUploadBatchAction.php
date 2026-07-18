<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Jobs\StoreDocumentJob;
use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Batch;
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
            ->get();

        if ($documents->isEmpty()) {
            return;
        }

        $ids = $documents->pluck('id');

        Bus::batch($documents->map(fn (Document $document): StoreDocumentJob => new StoreDocumentJob($document))->all())
            ->finally(fn (Batch $batch) => app(CountDocumentsUploadBatchOutcomeAction::class)->handle($ids))
            ->dispatch();
    }
}
