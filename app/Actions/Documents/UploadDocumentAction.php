<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentStatus;
use App\Models\Contracts\Documentable;
use App\Models\Document;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class UploadDocumentAction
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

    /**
     * @throws Throwable
     */
    public function handle(User $user, Documentable $documentable, UploadedFile $file): Document
    {
        $disk = config('documents.disk');
        $checksum = hash_file('sha256', $file->getRealPath());
        $path = $file->store('documents-staging', $disk);

        try {
            /** @var Document $document */
            $document = $documentable->documents()->create([
                'organization_id' => $this->organizationContext->id(),
                'uploaded_by' => $user->id,
                'original_filename' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_in_bytes' => $file->getSize(),
                'checksum' => $checksum,
                'status' => DocumentStatus::Pending,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        $document->loadMissing('uploadedBy');

        return $document;
    }
}
