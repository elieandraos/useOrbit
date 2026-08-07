<?php

declare(strict_types=1);

namespace App\Http\Requests\OrganizationMembers;

use App\Enums\OrganizationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

final class ChangeOrganizationMemberRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(Arr::pluck(OrganizationRole::invitableOptions(), 'value'))],
        ];
    }
}
