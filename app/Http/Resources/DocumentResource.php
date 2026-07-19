<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
final class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_in_bytes' => $this->size_in_bytes,
            'status' => $this->status,
            'uploaded_by_name' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy?->name),
            'download_url' => $this->when($this->status === DocumentStatus::Completed, fn () => route('documents.download', $this->resource)),
            'created_at' => $this->created_at->format('M j, Y · g:i A'),
        ];
    }
}
