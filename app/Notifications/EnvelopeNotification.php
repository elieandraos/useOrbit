<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for notifications delivering the standard envelope shape consumed by
 * the frontend (`NotificationEnvelope<TMeta>` in resources/js/types/notification.ts):
 * {action, actor, subject, meta, summary}.
 *
 * Concrete classes must also declare a `public const string ACTION` mirrored
 * by their action() implementation, since it's referenced statically.
 */
abstract class EnvelopeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected readonly ?User $actor = null) {}

    /**
     * @return array<int, string>
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    /**
     * @return array{
     *     action: string,
     *     actor: array{id: int, name: string}|null,
     *     subject: array{kind: string, slug: string, name: string},
     *     meta: array<string, mixed>,
     *     summary: string,
     * }
     */
    private function payload(): array
    {
        return [
            'action' => $this->action(),
            'actor' => $this->actorPayload(),
            'subject' => $this->subject(),
            'meta' => $this->meta(),
            'summary' => $this->summary(),
        ];
    }

    /** @return array{id: int, name: string}|null */
    private function actorPayload(): ?array
    {
        return $this->actor === null ? null : [
            'id' => $this->actor->id,
            'name' => $this->actor->name,
        ];
    }

    abstract protected function action(): string;

    /** @return array{kind: string, slug: string, name: string} */
    abstract protected function subject(): array;

    /** @return array<string, mixed> */
    abstract protected function meta(): array;

    abstract protected function summary(): string;
}
