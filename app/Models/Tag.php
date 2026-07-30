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
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property int $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $createdBy
 * @property-read int $taggables_count
 */
#[Fillable(['organization_id', 'name', 'created_by'])]
final class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use BelongsToCurrentOrganization, HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taggables(): HasMany
    {
        return $this->hasMany(TagAttachment::class);
    }

    #[Scope]
    protected function withTaggableCount(Builder $query, string $taggableType, ?string $ownerType = null, ?int $ownerId = null): Builder
    {
        return $query->withCount(['taggables' => function (Builder $query) use ($taggableType, $ownerType, $ownerId): void {
            /** @noinspection PhpUndefinedMethodInspection */
            $query->forTaggableType($taggableType, $ownerType, $ownerId);
        }]);
    }

    #[Scope]
    protected function relevantToTaggableType(Builder $query, string $taggableType): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query
            ->whereDoesntHave('taggables')
            ->orWhereHas('taggables', fn (Builder $query): Builder => $query->where('taggable_type', $taggableType)));
    }
}
