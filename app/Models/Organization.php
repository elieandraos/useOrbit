<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property OrganizationMember $pivot
 */
#[Fillable(['name'])]
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationMember::class)
            ->withPivot('role', 'status', 'invited_by', 'joined_at', 'token', 'expires_at')
            ->withTimestamps();
    }

    public function owner(): ?User
    {
        /** @var User|null $owner */
        $owner = $this->users()->wherePivot('role', OrganizationRole::Owner->value)->first();

        return $owner;
    }
}
