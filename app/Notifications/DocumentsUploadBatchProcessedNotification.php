<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class DocumentsUploadBatchProcessedNotification extends EnvelopeNotification
{
    public const string ACTION = 'documents.uploaded';

    /**
     * @param  array{completed: int, failed: int}  $outcome
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public readonly array $outcome,
        public readonly Collection $documents,
        public readonly Model&Documentable $documentable,
        ?User $actor = null,
    ) {
        parent::__construct($actor);
    }

    protected function action(): string
    {
        return self::ACTION;
    }

    /** @return array{kind: string, slug: string, name: string} */
    protected function subject(): array
    {
        return [
            'kind' => $this->documentable->documentableKind(),
            'slug' => (string) $this->documentable->getRouteKey(),
            'name' => $this->documentable->documentableName(),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     completed: int,
     *     failed: int,
     *     documents: array<int, array{id: int, status: string}>,
     * }
     */
    protected function meta(): array
    {
        return [
            'total' => $this->documents->count(),
            'completed' => $this->outcome['completed'],
            'failed' => $this->outcome['failed'],
            'documents' => $this->documents
                ->map(fn (Document $document): array => [
                    'id' => $document->id,
                    'status' => $document->status->value,
                ])
                ->all(),
        ];
    }

    protected function summary(): string
    {
        $total = $this->documents->count();
        $completed = $this->outcome['completed'];
        $failed = $this->outcome['failed'];
        $subject = "{$this->documentable->documentableKind()} {$this->documentable->documentableName()}";

        return $this->actor === null
            ? $this->selfSummary($total, $completed, $failed, $subject)
            : $this->attributedSummary($total, $completed, $failed, $subject, $this->actor->name);
    }

    private function selfSummary(int $total, int $completed, int $failed, string $subject): string
    {
        return match (true) {
            $failed === 0 => sprintf(
                '%d %s uploaded to %s.',
                $completed,
                Str::plural('document', $completed),
                $subject,
            ),
            $completed === 0 => sprintf(
                '%d %s failed to upload to %s.',
                $failed,
                Str::plural('document', $failed),
                $subject,
            ),
            default => sprintf(
                '%d of %d %s uploaded to %s — %d failed.',
                $completed,
                $total,
                Str::plural('document', $total),
                $subject,
                $failed,
            ),
        };
    }

    private function attributedSummary(int $total, int $completed, int $failed, string $subject, string $actorName): string
    {
        return match (true) {
            $failed === 0 => sprintf(
                '%s uploaded %d %s to %s.',
                $actorName,
                $completed,
                Str::plural('document', $completed),
                $subject,
            ),
            $completed === 0 => sprintf(
                '%s failed to upload %d %s to %s.',
                $actorName,
                $failed,
                Str::plural('document', $failed),
                $subject,
            ),
            default => sprintf(
                '%s uploaded %d of %d %s to %s — %d failed.',
                $actorName,
                $completed,
                $total,
                Str::plural('document', $total),
                $subject,
                $failed,
            ),
        };
    }
}
