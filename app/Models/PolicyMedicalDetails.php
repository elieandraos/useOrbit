<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MedicalClassTier;
use App\Enums\MedicalCoverageScope;
use Carbon\CarbonImmutable;
use Database\Factories\PolicyMedicalDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property MedicalCoverageScope $coverage_scope
 * @property MedicalClassTier $class_tier
 * @property bool $co_insurance
 * @property string|null $co_insurance_share
 * @property bool $guaranteed_renewable
 * @property string|null $insured_full_name
 * @property CarbonImmutable|null $insured_date_of_birth
 * @property Gender|null $insured_gender
 * @property bool|null $insured_smoker
 * @property string|null $insured_medical_history
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Policy $policy
 */
#[Fillable([
    'policy_id', 'coverage_scope', 'class_tier', 'co_insurance', 'co_insurance_share', 'guaranteed_renewable',
    'insured_full_name', 'insured_date_of_birth', 'insured_gender', 'insured_smoker', 'insured_medical_history',
])]
final class PolicyMedicalDetails extends Model
{
    /** @use HasFactory<PolicyMedicalDetailsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'coverage_scope' => MedicalCoverageScope::class,
            'class_tier' => MedicalClassTier::class,
            'co_insurance' => 'boolean',
            'co_insurance_share' => 'decimal:2',
            'guaranteed_renewable' => 'boolean',
            'insured_date_of_birth' => 'date',
            'insured_gender' => Gender::class,
            'insured_smoker' => 'boolean',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
