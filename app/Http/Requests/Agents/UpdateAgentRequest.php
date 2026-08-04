<?php

declare(strict_types=1);

namespace App\Http\Requests\Agents;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAgentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'building_floor' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where(fn (Builder $query) => $query->where('country_id', $this->input('country_id')))],
            'city' => ['nullable', 'string', 'max:100'],
        ];
    }
}
