<?php

declare(strict_types=1);

namespace App\Http\Requests\World;

use Illuminate\Foundation\Http\FormRequest;

final class SearchStatesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
