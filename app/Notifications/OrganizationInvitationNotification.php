<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrganizationInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Organization $organization,
        public readonly User $invitedBy,
        public readonly string $token,
    ) {}

    /**
     * @return array<int, string>
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You\'ve been invited to join :organization', ['organization' => $this->organization->name]))
            ->line(__(':name has invited you to join :organization on :app.', [
                'name' => $this->invitedBy->name,
                'organization' => $this->organization->name,
                'app' => config('app.name'),
            ]))
            ->action(__('Accept Invitation'), route('invitations.show', $this->token))
            ->line(__('This invitation will expire in 7 days.'));
    }
}
