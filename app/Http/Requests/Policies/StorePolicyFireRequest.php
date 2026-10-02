<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyValidationRules;
use App\Enums\PolicyClass;
use App\Enums\PolicyStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePolicyFireRequest extends FormRequest
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
            ...$this->policyRules(PolicyClass::Fire),

            'fire.property_type' => ['required', 'string', 'max:50'],
            'fire.floor_area' => ['required', 'integer', 'min:1', 'max:65535'],
            'fire.year_built' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year)],
            'fire.street' => ['required', 'string', 'max:255'],
            'fire.building_floor' => ['nullable', 'string', 'max:255'],
            'fire.city' => ['required', 'string', 'max:100'],
            'fire.country_id' => ['required', 'integer', Rule::exists('countries', 'id')->whereIn('iso2', config('markets.countries'))],
            'fire.state_id' => ['required', 'integer', Rule::exists('states', 'id')->where(fn (Builder $query) => $query->where('country_id', $this->input('fire.country_id')))],
            'fire.sum_insured' => ['required', ...$this->policyAmountRules()],
        ];
    }

    /**
     * Get the readable names of the fields, as the Fire policy form labels them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...$this->policyAttributes(),

            'fire.property_type' => 'property type',
            'fire.floor_area' => 'floor area',
            'fire.year_built' => 'year built',
            'fire.street' => 'street',
            'fire.building_floor' => 'building / floor',
            'fire.city' => 'city',
            'fire.country_id' => 'country',
            'fire.state_id' => 'governorate',
            'fire.sum_insured' => 'sum insured',
        ];
    }
}
