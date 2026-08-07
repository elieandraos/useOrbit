<?php

declare(strict_types=1);

namespace App\Http\Requests\OrganizationInvitations;

use App\Concerns\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class AcceptOrganizationInvitationRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }
}
