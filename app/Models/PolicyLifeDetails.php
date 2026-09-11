<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PolicyLifeDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $sum_assured
 * @property int $term_years
 * @property bool $smoker
 * @property string $beneficiaries
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 */
#[Fillable([
    'policy_id', 'sum_assured', 'term_years', 'smoker', 'beneficiaries',
])]
final class PolicyLifeDetails extends Model
{
    /** @use HasFactory<PolicyLifeDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sum_assured' => 'decimal:2',
            'term_years' => 'integer',
            'smoker' => 'boolean',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
