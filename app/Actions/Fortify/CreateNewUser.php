<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array{organization: string, name: string, email: string, password: string, password_confirmation: string}  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'organization' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            /** @var Organization $organization */
            $organization = Organization::query()->create([
                'name' => $input['organization'],
            ]);

            /** @var User $user */
            $user = User::query()->create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'current_organization_id' => $organization->id,
            ]);

            $user->organizations()->attach($organization->id, [
                'role' => OrganizationRole::Owner->value,
                'status' => OrganizationMemberStatus::Active->value,
                'joined_at' => now(),
            ]);

            return $user;
        });
    }
}
