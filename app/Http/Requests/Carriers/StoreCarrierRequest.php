<?php

declare(strict_types=1);

namespace App\Http\Requests\Carriers;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCarrierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],

            'branch.street' => ['nullable', 'string', 'max:255'],
            'branch.building_floor' => ['nullable', 'string', 'max:255'],
            'branch.city' => ['required', 'string', 'max:100'],
            'branch.country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'branch.state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where(fn (Builder $query) => $query->where('country_id', $this->input('branch.country_id')))],

            'contact.name' => ['required', 'string', 'max:150'],
            'contact.role' => ['nullable', 'string', 'max:100'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Get the readable names of the nested branch and contact fields, as the Carrier form labels them.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch.street' => 'branch street',
            'branch.building_floor' => 'branch building / floor',
            'branch.city' => 'branch city',
            'branch.country_id' => 'branch country',
            'branch.state_id' => 'branch state',

            'contact.name' => 'contact name',
            'contact.role' => 'contact title / role',
            'contact.email' => 'contact email',
            'contact.phone' => 'contact phone',
        ];
    }
}
