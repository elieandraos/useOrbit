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
        $pivot = $this->pivot;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $pivot->role,
            'status' => $pivot->status,
            'joined_at' => $pivot->joined_at?->format('Y-m-d'),
            'is_you' => $this->id === $request->user()?->id,
        ];
    }
}
