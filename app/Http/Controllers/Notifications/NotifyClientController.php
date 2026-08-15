<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\NotifyAction;
use App\Enums\NotificationReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotifyRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class NotifyClientController extends Controller
{
    #[Authorize('view', 'client')]
    public function __invoke(NotifyRequest $request, Client $client, NotifyAction $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $action->handle($actor, $client, $request->validated('recipient_ids'), NotificationReason::from($request->validated('reason')));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification sent.')]);

        return back();
    }
}
