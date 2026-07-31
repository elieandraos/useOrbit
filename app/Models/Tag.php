<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property int $created_by
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $createdBy
 * @property-read User|null $updatedBy
 * @property-read int $documents_count
 */
#[Fillable(['organization_id', 'name', 'created_by', 'updated_by'])]
final class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use BelongsToCurrentOrganization, HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class)->withTimestamps();
    }

    #[Scope]
    protected function withDocumentCount(Builder $query, string $ownerType, int $ownerId): Builder
    {
        return $query->withCount(['documents' => fn (Builder $query): Builder => $query
            ->where('documentable_type', $ownerType)
            ->where('documentable_id', $ownerId)]);
    }
}
