<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Client;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

final class DocumentsUploadBatchProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{completed: int, failed: int}  $outcome
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public readonly array $outcome,
        public readonly Collection $documents,
        public readonly Client $client,
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
     *     total: int,
     *     completed: int,
     *     failed: int,
     *     documents: array<int, array{id: int, status: string}>,
     *     client: array{slug: string, name: string},
     *     summary: string,
     * }
     */
    private function payload(): array
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
            'client' => [
                'slug' => $this->client->slug,
                'name' => "{$this->client->first_name} {$this->client->last_name}",
            ],
            'summary' => $this->summary(),
        ];
    }

    private function summary(): string
    {
        $total = $this->documents->count();
        $completed = $this->outcome['completed'];
        $failed = $this->outcome['failed'];

        return match (true) {
            $failed === 0 => "$completed of $total documents uploaded successfully.",
            $completed === 0 => "$failed of $total documents failed to upload.",
            default => "$completed of $total documents uploaded, $failed failed.",
        };
    }
}
