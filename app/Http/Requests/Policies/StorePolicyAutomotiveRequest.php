<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\PolicyClass;
use App\Enums\PolicyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePolicyAutomotiveRequest extends FormRequest
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
        $isAllRisk = $this->input('subclass') === 'All Risk';

        return [
            ...$this->policyRules(PolicyClass::Automotive),

            'automotive.plate_number' => ['required', 'string', 'max:20'],
            'automotive.make' => ['required', 'string', 'max:50'],
            'automotive.model' => ['required', 'string', 'max:50'],
            'automotive.year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'automotive.vin' => ['nullable', 'string', 'max:50'],
            'automotive.color' => ['nullable', 'string', 'max:30'],
            'automotive.valuation_amount' => [Rule::requiredIf($isAllRisk), Rule::prohibitedIf(! $isAllRisk), 'nullable', 'numeric', 'min:0'],
            'automotive.valuation_source' => [Rule::requiredIf($isAllRisk), Rule::prohibitedIf(! $isAllRisk), 'nullable', 'string', 'max:50'],
        ];
    }
}
