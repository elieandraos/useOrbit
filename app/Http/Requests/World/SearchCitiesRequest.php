<?php

declare(strict_types=1);

namespace App\Http\Requests\World;

use Illuminate\Foundation\Http\FormRequest;

final class SearchCitiesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
