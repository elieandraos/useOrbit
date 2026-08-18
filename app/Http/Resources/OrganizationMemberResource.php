<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class OrganizationMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at?->format('Y-m-d'),
            'last_login_at' => $this->last_login_at?->diffForHumans(),
            'is_you' => $this->id === $request->user()?->id,
            'can_change_role' => $request->user()?->can('changeRole', [User::class, $this->resource]) ?? false,
            'can_remove' => $request->user()?->can('remove', [User::class, $this->resource]) ?? false,
            'can_revoke' => $request->user()?->can('revoke', [User::class, $this->resource]) ?? false,
            'can_reset_two_factor' => $request->user()?->can('resetTwoFactor', [User::class, $this->resource]) ?? false,
        ];
    }
}
