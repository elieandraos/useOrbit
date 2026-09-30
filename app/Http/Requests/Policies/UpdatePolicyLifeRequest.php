<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\PolicyClass;
use App\Enums\PolicyStatus;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePolicyLifeRequest extends FormRequest
{
    use PolicyValidationRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?? PolicyStatus::Active->value,
            'life' => [
                ...$this->input('life', []),
                'smoker' => $this->boolean('life.smoker'),
            ],
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->policyRules(PolicyClass::Life),

            'life.sum_assured' => ['required', 'numeric', 'min:0'],
            'life.term_years' => ['required', 'integer', 'min:1', 'max:100'],
            'life.smoker' => ['required', 'boolean'],
            'life.beneficiaries' => ['required', 'string'],
        ];
    }

    /**
     * Get the readable names of the fields, as the Life policy form labels them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...$this->policyAttributes(),

            'life.sum_assured' => 'sum assured',
            'life.term_years' => 'term in years',
            'life.smoker' => 'smoker',
            'life.beneficiaries' => 'beneficiaries',
        ];
    }
}
