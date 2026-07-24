<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

final class StoreDocumentJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public function __construct(public readonly Document $document) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $document = $this->claim();

        if (! $document instanceof Document) {
            return;
        }

        $disk = config('documents.disk');
        $destination = $this->destinationPath($document);

        if (! Storage::disk($disk)->exists($destination)) {
            Storage::disk($disk)->move($document->path, $destination);
        }

        $document->update([
            'disk' => $disk,
            'path' => $destination,
            'status' => DocumentStatus::Completed,
            'stored_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->document->update([
            'status' => DocumentStatus::Failed,
            'error_message' => $exception?->getMessage(),
        ]);
    }

    private function claim(): ?Document
    {
        return DB::transaction(function (): ?Document {
            /** @var Document|null $document */
            $document = Document::query()->whereKey($this->document->id)->lockForUpdate()->first();

            if (! $document instanceof Document || $document->status !== DocumentStatus::Pending) {
                return null;
            }

            return $document;
        });
    }

    private function destinationPath(Document $document): string
    {
        return sprintf(
            'organizations/%d/%s/%d/%d.%s',
            $document->organization_id,
            $document->documentable_type,
            $document->documentable_id,
            $document->id,
            $this->extension($document),
        );
    }

    private function extension(Document $document): string
    {
        $allowed = config('documents.allowed_mimes');
        $candidates = MimeTypes::getDefault()->getExtensions($document->mime_type);

        /** @var string|null $extension */
        $extension = collect($candidates)->first(fn (string $candidate): bool => in_array($candidate, $allowed, true));

        if ($extension === null) {
            throw new RuntimeException("Document [$document->id] has no validated extension for mime type [$document->mime_type].");
        }

        return $extension;
    }
}
