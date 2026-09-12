<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use Carbon\CarbonImmutable;
use Database\Factories\PolicyInsuredFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $member_code
 * @property string $full_name
 * @property string $relationship
 * @property CarbonImmutable $date_of_birth
 * @property Gender|null $gender
 * @property string|null $medical_notes
 * @property string $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 */
#[Fillable([
    'policy_id', 'member_code', 'full_name', 'relationship', 'date_of_birth', 'gender', 'medical_notes', 'status',
])]
final class PolicyInsured extends Model
{
    /** @use HasFactory<PolicyInsuredFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
