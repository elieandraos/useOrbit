<?php

declare(strict_types=1);

namespace App\Http\Requests\OrganizationMembers;

use App\Enums\OrganizationMemberStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RemoveOrganizationMemberRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var User $member */
        $member = $this->route('member');

        /** @var User $user */
        $user = $this->user();

        return [
            'reassign_to' => [
                'required',
                'integer',
                Rule::notIn([$member->id]),
                Rule::exists('organization_user', 'user_id')
                    ->where('organization_id', $user->current_organization_id)
                    ->where('status', OrganizationMemberStatus::Active->value),
            ],
        ];
    }
}
