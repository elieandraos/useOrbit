<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Enums\ClientType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class IndexClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'client_type' => ['nullable', new Enum(ClientType::class)],
            'gender' => ['nullable', new Enum(Gender::class)],
            'enrolled_from' => ['nullable', 'date'],
            'enrolled_to' => ['nullable', 'date', 'after_or_equal:enrolled_from'],
            'age_min' => ['nullable', 'integer', 'min:0'],
            'age_max' => [
                'nullable',
                'integer',
                'min:0',
                Rule::when($this->filled('age_min') && $this->filled('age_max'), ['gte:age_min']),
            ],
            'archived' => ['boolean'],
            'sort' => ['nullable', 'in:name,enrollment_date,type'],
            'direction' => ['in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'archived' => $this->boolean('archived'),
            'direction' => $this->input('direction') ?? ($this->filled('sort') ? 'asc' : 'desc'),
        ]);
    }
}
