<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Contracts\Documentable;
use App\Models\Scopes\CurrentOrganizationScope;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

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
 * @property string|null $checksum
 * @property DocumentStatus $status
 * @property CarbonImmutable|null $stored_at
 * @property string|null $error_message
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $uploadedBy
 * @property-read Documentable $documentable
 * @property-read Collection<int, Tag> $tags
 */
#[Fillable([
    'organization_id', 'documentable_type', 'documentable_id', 'uploaded_by',
    'original_filename', 'disk', 'path', 'mime_type', 'size_in_bytes', 'checksum',
    'status', 'stored_at', 'error_message',
])]
final class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToCurrentOrganization, HasFactory, Prunable;

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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /** @return Collection<int, Tag> */
    public function tagsWithUsageCounts(): Collection
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->tags()
            ->withDocumentCount($this->documentable_type, $this->documentable_id)
            ->get();
    }

    public function prunable(): Builder
    {
        return self::query()
            ->withoutGlobalScope(CurrentOrganizationScope::class)
            ->whereIn('status', [DocumentStatus::Pending, DocumentStatus::Processing, DocumentStatus::Failed])
            ->where('created_at', '<', now()->subHours(config('documents.prune_after_hours')));
    }

    protected function pruning(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
