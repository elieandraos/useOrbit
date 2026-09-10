<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PolicyAutomotiveDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $plate_number
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string|null $vin
 * @property string|null $color
 * @property string|null $valuation_amount
 * @property string|null $valuation_source
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 */
#[Fillable([
    'policy_id', 'plate_number', 'make', 'model', 'year', 'vin', 'color',
    'valuation_amount', 'valuation_source',
])]
final class PolicyAutomotiveDetails extends Model
{
    /** @use HasFactory<PolicyAutomotiveDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'valuation_amount' => 'decimal:2',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
