<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Contracts\Documentable;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property int $uploaded_by
 * @property string $original_filename
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_in_bytes
 * @property DocumentStatus $status
 * @property CarbonImmutable|null $stored_at
 * @property string|null $error_message
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $uploadedBy
 * @property-read Documentable $documentable
 */
#[Fillable([
    'organization_id', 'documentable_type', 'documentable_id', 'uploaded_by',
    'original_filename', 'disk', 'path', 'mime_type', 'size_in_bytes',
    'status', 'stored_at', 'error_message',
])]
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToCurrentOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'stored_at' => 'datetime',
            'status' => DocumentStatus::class,
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
