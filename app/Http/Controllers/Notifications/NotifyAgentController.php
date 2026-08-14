<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\NotifyAction;
use App\Enums\NotificationReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotifyRequest;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class NotifyAgentController extends Controller
{
    #[Authorize('view', 'agent')]
    public function __invoke(NotifyRequest $request, Agent $agent, NotifyAction $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $action->handle($actor, $agent, $request->validated('recipient_ids'), NotificationReason::from($request->validated('reason')));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification sent.')]);

        return back();
    }
}
