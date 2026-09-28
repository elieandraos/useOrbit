<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\PolicyClass;
use App\Enums\PolicyStatus;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePolicyTravelRequest extends FormRequest
{
    use PolicyValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?? PolicyStatus::Active->value,
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->policyRules(PolicyClass::Travel),

            'travel.destination' => ['required', 'string', 'max:255'],
            'travel.trip_start_date' => ['required', 'date'],
            'travel.trip_end_date' => ['required', 'date', 'after_or_equal:travel.trip_start_date'],
            'travel.travelers' => ['required', 'string'],
            'travel.coverage_tier' => ['required', 'string', 'max:20'],
        ];
    }
}
