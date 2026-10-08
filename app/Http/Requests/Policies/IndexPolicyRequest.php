<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Concerns\PolicyAmountValidationRules;
use App\Enums\PolicyClass;
use App\Enums\PolicyDisplayStatus;
use App\Enums\PolicySource;
use App\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class IndexPolicyRequest extends FormRequest
{
    use PolicyAmountValidationRules;

    /**
     * Amounts are only comparable within one currency, so the amount bounds require a currency.
     */
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $hasCurrency = $this->filled('currency_id');

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', new Enum(PolicyDisplayStatus::class)],
            'type' => ['nullable', new Enum(PolicyType::class)],
            'class' => ['nullable', 'array'],
            'class.*' => [new Enum(PolicyClass::class)],
            'carrier_id' => ['nullable', 'integer', Rule::exists('carriers', 'id')->where('organization_id', $organizationId)],
            'source' => ['nullable', new Enum(PolicySource::class)],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'amount_min' => [Rule::prohibitedIf(! $hasCurrency), 'nullable', ...$this->policyAmountRules()],
            'amount_max' => [
                Rule::prohibitedIf(! $hasCurrency),
                'nullable',
                ...$this->policyAmountRules(),
                Rule::when($this->filled('amount_min') && $this->filled('amount_max'), ['gte:amount_min']),
            ],
        ];
    }
}
