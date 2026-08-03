<?php

declare(strict_types=1);

namespace App\Http\Requests\Carriers;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCarrierBranchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'street' => ['nullable', 'string', 'max:255'],
            'building_floor' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where(fn (Builder $query) => $query->where('country_id', $this->input('country_id')))],

            'contact_name' => ['required', 'string', 'max:150'],
            'contact_role' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
