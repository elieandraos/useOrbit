<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\PolicyClass;
use App\Enums\TravelCoverageTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdatePolicyTravelRequest extends FormRequest
{
    use PolicyValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->policyRules(PolicyClass::Travel),

            'travel.destination' => ['required', 'string', 'max:255'],
            'travel.trip_start_date' => ['required', 'date', 'after_or_equal:effective_date'],
            'travel.trip_end_date' => ['required', 'date', 'after_or_equal:travel.trip_start_date', 'before_or_equal:expiry_date'],
            'travel.travelers' => ['required', 'string'],
            'travel.coverage_tier' => ['required', new Enum(TravelCoverageTier::class)],
        ];
    }

    /**
     * Get the readable names of the fields, as the Travel policy form labels them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...$this->policyAttributes(),

            'travel.destination' => 'destination',
            'travel.trip_start_date' => 'trip start date',
            'travel.trip_end_date' => 'trip end date',
            'travel.travelers' => 'travelers',
            'travel.coverage_tier' => 'coverage tier',
        ];
    }
}
