<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class NotificationsMarkReadController extends Controller
{
    public function __invoke(Request $request, string $notification, MarkNotificationAsReadAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user, $notification);

        return response()->noContent();
    }
}
