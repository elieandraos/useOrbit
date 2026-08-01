<?php

declare(strict_types=1);

namespace App\Http\Requests\Carriers;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCarrierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'onboarded_date' => ['required', 'date'],

            'branch.street' => ['nullable', 'string', 'max:255'],
            'branch.building_floor' => ['nullable', 'string', 'max:255'],
            'branch.city' => ['required', 'string', 'max:100'],
            'branch.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'branch.state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where(fn (Builder $query) => $query->where('country_id', $this->input('branch.country_id')))],
            'branch.phone' => ['nullable', 'string', 'max:30'],

            'contact.name' => ['required', 'string', 'max:150'],
            'contact.role' => ['nullable', 'string', 'max:100'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.department' => ['nullable', 'string', 'max:100'],
        ];
    }
}
