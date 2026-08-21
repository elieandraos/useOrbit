<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\OrganizationMembers\ResetTwoFactorAuthenticationAction;
use App\Enums\OrganizationRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature("organizations:reset-owner-two-factor
    {email : The locked-out Owner's email address}")]
#[Description("Reset an organization Owner's two-factor authentication when no other Owner can")]
final class ResetOwnerTwoFactorAuthentication extends Command implements PromptsForMissingInput
{
    /**
     * @throws \Throwable
     */
    public function handle(ResetTwoFactorAuthenticationAction $action): int
    {
        $email = (string) $this->argument('email');

        $owner = User::query()->where('email', $email)->first();

        if (! $owner instanceof User || $owner->role !== OrganizationRole::Owner) {
            $this->components->error("No Owner found with the email [{$email}].");

            return self::FAILURE;
        }

        if (! $this->confirm("Reset two-factor authentication for {$owner->email}? This cannot be undone.")) {
            $this->components->warn('Command cancelled.');

            return self::FAILURE;
        }

        $action->handle(null, $owner);

        $this->components->info('Two-factor authentication reset.');
        $this->components->twoColumnDetail('Owner email', $owner->email);

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'email' => "What is the locked-out Owner's email address?",
        ];
    }
}
