<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $document = $this->claim();

        if (! $document instanceof Document) {
            return;
        }

        try {
            $disk = config('documents.disk');
            $destination = $this->destinationPath($document);

            if (! $this->alreadyStored($disk, $destination, $document->checksum)) {
                if (! Storage::disk($disk)->exists($document->path)) {
                    throw new RuntimeException("Document [$document->id] staged file is missing and the destination copy failed its integrity check.");
                }

                Storage::disk($disk)->move($document->path, $destination);
            }

            $document->update([
                'disk' => $disk,
                'path' => $destination,
                'status' => DocumentStatus::Completed,
                'stored_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $document->update(['status' => DocumentStatus::Pending]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to store an uploaded document.', [
            'document_id' => $this->document->id,
            'exception' => $exception?->getMessage(),
        ]);

        $this->document->update([
            'status' => DocumentStatus::Failed,
            'error_message' => 'We were unable to store this file. Please try uploading it again.',
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

            $document->update(['status' => DocumentStatus::Processing]);

            return $document;
        });
    }

    private function alreadyStored(string $disk, string $destination, ?string $expectedChecksum): bool
    {
        if (! Storage::disk($disk)->exists($destination)) {
            return false;
        }

        if ($expectedChecksum === null) {
            return true;
        }

        return $this->checksum($disk, $destination) === $expectedChecksum;
    }

    private function checksum(string $disk, string $path): ?string
    {
        $stream = Storage::disk($disk)->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        return hash_final($context);
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
