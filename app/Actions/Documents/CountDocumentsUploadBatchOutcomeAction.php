<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Support\Collection;

final class CountDocumentsUploadBatchOutcomeAction
{
    /**
     * @param  Collection<int, int>  $documentIds
     * @return array{completed: int, failed: int}
     */
    public function handle(Collection $documentIds): array
    {
        $statuses = Document::query()->whereKey($documentIds)->pluck('status');

        return [
            'completed' => $statuses->filter(fn (DocumentStatus $status): bool => $status === DocumentStatus::Completed)->count(),
            'failed' => $statuses->filter(fn (DocumentStatus $status): bool => $status === DocumentStatus::Failed)->count(),
        ];
    }
}
