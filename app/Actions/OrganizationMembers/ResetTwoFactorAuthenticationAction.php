<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\User;
use App\Notifications\YourTwoFactorAuthenticationWasResetNotification;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

final readonly class ResetTwoFactorAuthenticationAction
{
    public function __construct(private DisableTwoFactorAuthentication $disableTwoFactorAuthentication) {}

    /**
     * @throws \Throwable
     */
    public function handle(User $actor, User $member): void
    {
        DB::transaction(function () use ($member): void {
            ($this->disableTwoFactorAuthentication)($member);
        });

        $member->notify(new YourTwoFactorAuthenticationWasResetNotification($actor, $member->organization)->afterCommit());
    }
}
