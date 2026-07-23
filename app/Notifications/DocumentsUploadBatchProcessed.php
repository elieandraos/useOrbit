<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class DocumentsUploadBatchProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public const string ACTION = 'documents.uploaded';

    /**
     * @param  array{completed: int, failed: int}  $outcome
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public readonly array $outcome,
        public readonly Collection $documents,
        public readonly Model&Documentable $documentable,
        public readonly ?User $actor = null,
    ) {}

    /**
     * @return array<int, string>
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    /**
     * @return array{
     *     action: string,
     *     actor: array{id: int, name: string}|null,
     *     subject: array{kind: string, slug: string, name: string},
     *     meta: array{
     *         total: int,
     *         completed: int,
     *         failed: int,
     *         documents: array<int, array{id: int, status: string}>,
     *     },
     *     summary: string,
     * }
     */
    private function payload(): array
    {
        return [
            'action' => self::ACTION,
            'actor' => $this->actor === null ? null : [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
            'subject' => [
                'kind' => $this->documentable->documentableKind(),
                'slug' => (string) $this->documentable->getRouteKey(),
                'name' => $this->documentable->documentableName(),
            ],
            'meta' => [
                'total' => $this->documents->count(),
                'completed' => $this->outcome['completed'],
                'failed' => $this->outcome['failed'],
                'documents' => $this->documents
                    ->map(fn (Document $document): array => [
                        'id' => $document->id,
                        'status' => $document->status->value,
                    ])
                    ->all(),
            ],
            'summary' => $this->summary(),
        ];
    }

    private function summary(): string
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
