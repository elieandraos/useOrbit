<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Enums\ClientType;
use App\Enums\EmergencyContactRelationship;
use App\Enums\Gender;
use App\Enums\LeadSource;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreClientRequest extends FormRequest
{
    public function rules(): array
    {
        $isCompany = $this->input('client_type') === ClientType::Company->value;

        return [
            'client_type' => ['required', new Enum(ClientType::class)],
            'company_name' => [Rule::requiredIf($isCompany), Rule::prohibitedIf(! $isCompany), 'nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'date_of_birth' => [Rule::requiredIf(! $isCompany), Rule::prohibitedIf($isCompany), 'date'],
            'gender' => [Rule::requiredIf(! $isCompany), Rule::prohibitedIf($isCompany), new Enum(Gender::class)],
            'enrollment_date' => ['required', 'date'],
            'lead_source' => ['required', new Enum(LeadSource::class)],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'mothers_name' => [Rule::prohibitedIf($isCompany), 'nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($isCompany), 'nullable', 'email', 'max:255'],
            'photo' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'building_floor' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where(fn (Builder $query) => $query->where('country_id', $this->input('country_id')))],
            'city' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => [Rule::prohibitedIf($isCompany), 'nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => [Rule::prohibitedIf($isCompany), 'nullable', new Enum(EmergencyContactRelationship::class)],
            'emergency_contact_phone' => [Rule::prohibitedIf($isCompany), 'nullable', 'string', 'max:255'],
        ];
    }
}
