<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class IndexClientRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'search' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', new Enum(Gender::class)],
            'enrolled_from' => ['nullable', 'date'],
            'enrolled_to' => ['nullable', 'date', 'after_or_equal:enrolled_from'],
            'age_min' => ['nullable', 'integer', 'min:0'],
            'age_max' => ['nullable', 'integer', 'min:0'],
            'archived' => ['nullable', 'boolean'],
        ];

        if ($this->filled('age_min') && $this->filled('age_max')) {
            $rules['age_max'][] = 'gte:age_min';
        }

        return $rules;
    }
}
