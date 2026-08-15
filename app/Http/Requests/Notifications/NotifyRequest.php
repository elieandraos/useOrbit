<?php

declare(strict_types=1);

namespace App\Http\Requests\Notifications;

use App\Enums\NotificationReason;
use App\Enums\OrganizationMemberStatus;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotifyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(NotificationReason::class)],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => [
                'distinct',
                'integer',
                Rule::notIn([$this->user()->id]),
                Rule::exists('users', 'id')
                    ->where('organization_id', app(OrganizationContext::class)->id())
                    ->where('status', OrganizationMemberStatus::Active->value),
            ],
        ];
    }
}
