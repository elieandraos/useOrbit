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
                Rule::exists('users', 'id')
                    ->where('organization_id', $user->organization_id)
                    ->where('status', OrganizationMemberStatus::Active->value),
            ],
        ];
    }
}
