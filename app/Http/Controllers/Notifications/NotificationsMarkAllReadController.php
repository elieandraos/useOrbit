<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class NotificationsMarkAllReadController extends Controller
{
    public function __invoke(Request $request, MarkAllNotificationsAsReadAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user);

        return response()->noContent();
    }
}
