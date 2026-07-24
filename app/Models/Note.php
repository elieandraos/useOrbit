<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentOrganization;
use App\Models\Contracts\Notable;
use Carbon\CarbonImmutable;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $notable_type
 * @property int $notable_id
 * @property int $created_by
 * @property string $body
 * @property bool $pinned
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $createdBy
 * @property-read Notable $notable
 */
#[Fillable([
    'organization_id', 'notable_type', 'notable_id', 'created_by', 'body', 'pinned',
])]
final class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use BelongsToCurrentOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
        ];
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
