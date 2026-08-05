<?php

declare(strict_types=1);

namespace App\Http\Requests\OrganizationMembers;

use App\Enums\OrganizationRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class InviteOrganizationMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(Arr::pluck(OrganizationRole::invitableOptions(), 'value'))],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $invitee = User::query()->where('email', $this->input('email'))->first();

                if ($invitee === null) {
                    return;
                }

                /** @var User $user */
                $user = $this->user();

                $alreadyInOrganization = $invitee->organizations()
                    ->wherePivot('organization_id', $user->current_organization_id)
                    ->exists();

                $validator->errors()->add('email', $alreadyInOrganization
                    ? __('This person is already a member of this organization.')
                    : __('This email is already registered.'));
            },
        ];
    }
}
