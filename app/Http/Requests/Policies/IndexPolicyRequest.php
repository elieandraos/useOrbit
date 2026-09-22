<?php

declare(strict_types=1);

namespace App\Http\Requests\Policies;

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class IndexPolicyRequest extends FormRequest
{
    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', new Enum(PolicyStatus::class)],
            'type' => ['nullable', new Enum(PolicyType::class)],
            'class' => ['nullable', 'array'],
            'class.*' => [new Enum(PolicyClass::class)],
            'carrier_id' => ['nullable', 'integer', Rule::exists('carriers', 'id')->where('organization_id', $organizationId)],
            'source' => ['nullable', new Enum(PolicySource::class)],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->filled('amount_min') && $this->filled('amount_max'), ['gte:amount_min']),
            ],
            'sort' => ['nullable', 'in:policy_number,client,effective_date,amount,status'],
            'direction' => ['in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'direction' => $this->input('direction') ?? ($this->filled('sort') ? 'asc' : 'desc'),
        ]);
    }
}
