<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\NotifyAction;
use App\Enums\NotificationReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotifyRequest;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class NotifyDocumentController extends Controller
{
    #[Authorize('view', 'document')]
    public function __invoke(NotifyRequest $request, Document $document, NotifyAction $action): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $action->handle($actor, $document, $request->validated('recipient_ids'), NotificationReason::from($request->validated('reason')));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification sent.')]);

        return back();
    }
}
