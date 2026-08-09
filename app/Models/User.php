<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Scopes\CurrentOrganizationScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Carbon|null $email_verified_at
 * @property OrganizationRole $role
 * @property OrganizationMemberStatus $status
 * @property int|null $invited_by
 * @property Carbon|null $joined_at
 * @property string|null $invitation_token
 * @property Carbon|null $invitation_expires_at
 * @property Carbon|null $last_login_at
 * @property int|null $country_id
 * @property-read Organization $organization
 * @property-read Country|null $country
 * @property-read User|null $inviter
 */
#[Fillable(['name', 'email', 'password', 'organization_id', 'role', 'status', 'invited_by', 'joined_at', 'invitation_token', 'invitation_expires_at', 'country_id', 'email_verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Prunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => OrganizationRole::class,
            'status' => OrganizationMemberStatus::class,
            'joined_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by');
    }

    #[Scope]
    protected function pendingInvitation(Builder $query): Builder
    {
        return $query->where('status', OrganizationMemberStatus::Invited->value)
            ->whereNotNull('invitation_token')
            ->where('invitation_expires_at', '>', now());
    }

    public function prunable(): Builder
    {
        return self::query()
            ->withoutGlobalScope(CurrentOrganizationScope::class)
            ->whereNull('password')
            ->where('status', OrganizationMemberStatus::Invited->value)
            ->where('invitation_expires_at', '<', now());
    }
}
