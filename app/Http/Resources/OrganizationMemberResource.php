<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class OrganizationMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $pivot->role,
            'status' => $pivot->status,
            'joined_at' => $pivot->joined_at?->format('Y-m-d'),
            'last_login_at' => $this->last_login_at?->diffForHumans(),
            'is_you' => $this->id === $request->user()?->id,
            'can_change_role' => $request->user()?->can('changeRole', [OrganizationMember::class, $this->resource]) ?? false,
            'can_remove' => $request->user()?->can('remove', [OrganizationMember::class, $this->resource]) ?? false,
            'can_revoke' => $request->user()?->can('revoke', [OrganizationMember::class, $this->resource]) ?? false,
        ];
    }
}
