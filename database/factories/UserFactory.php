<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'organization_id' => Organization::factory(),
            'role' => OrganizationRole::Owner,
            'status' => OrganizationMemberStatus::Active,
            'joined_at' => now(),
            'country_id' => null,
        ];
    }

    /**
     * Indicate that the user belongs to an active organization.
     */
    public function withOrganization(): static
    {
        return $this->state(fn (): array => [
            'organization_id' => Organization::factory(),
            'role' => OrganizationRole::Member,
            'status' => OrganizationMemberStatus::Active,
            'joined_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is an active member of the given organization.
     */
    public function forOrganization(Organization $organization, OrganizationRole $role = OrganizationRole::Member): static
    {
        return $this->state(fn (): array => [
            'organization_id' => $organization->id,
            'role' => $role,
            'status' => OrganizationMemberStatus::Active,
            'joined_at' => now(),
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(function (): array {
            $secret = app(Google2FA::class)->generateSecretKey();

            return [
                'two_factor_secret' => Crypt::encrypt($secret),
                'two_factor_recovery_codes' => Crypt::encrypt(json_encode(
                    Collection::times(8, fn (): string => RecoveryCode::generate())->all(),
                )),
                'two_factor_confirmed_at' => now(),
            ];
        });
    }
}
