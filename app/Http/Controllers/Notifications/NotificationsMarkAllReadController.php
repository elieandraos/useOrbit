<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class NotificationsMarkAllReadController extends Controller
{
    public function __invoke(Request $request, MarkAllNotificationsAsReadAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user);

        return back();
    }
}
