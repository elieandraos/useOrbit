<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class OrganizationDetailsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'default_country_id' => ['present', 'nullable', 'integer', 'exists:countries,id'],
            'default_currency_id' => ['present', 'nullable', 'integer', 'exists:currencies,id'],
            'timezone' => ['present', 'nullable', 'timezone:all'],
        ];
    }
}
