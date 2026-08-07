<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property OrganizationRole $role
 * @property OrganizationMemberStatus $status
 * @property int|null $invited_by
 * @property Carbon|null $joined_at
 * @property string|null $token
 * @property Carbon|null $expires_at
 * @property-read User $user
 * @property-read Organization $organization
 * @property-read User|null $inviter
 */
final class OrganizationMember extends Pivot
{
    protected $table = 'organization_user';

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'status' => OrganizationMemberStatus::class,
            'joined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    #[Scope]
    protected function pendingInvitation(Builder $query): Builder
    {
        return $query->where('status', OrganizationMemberStatus::Invited->value)
            ->whereNotNull('token')
            ->where('expires_at', '>', now());
    }
}
