<?php

declare(strict_types=1);

namespace App\Support\OrganizationMembers;

use App\Enums\OrganizationMemberStatus;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;

final readonly class ActiveOrganizationRecipients
{
    public function __construct(private OrganizationContext $organizationContext) {}

    /** @return Builder<User> */
    public function query(): Builder
    {
        return User::query()
            ->where('organization_id', $this->organizationContext->id())
            ->where('status', OrganizationMemberStatus::Active->value);
    }
}
