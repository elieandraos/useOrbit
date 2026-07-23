<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationsListController extends Controller
{
    private const int PER_PAGE = 15;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // notifications() applies its own latest()/orderByDesc('created_at') by
        // default, which would otherwise take precedence over is_read below.
        $paginator = $user->notifications()
            ->reorder()
            ->select('*')
            ->selectRaw('(case when read_at is null then 0 else 1 end) as is_read')
            ->orderBy('is_read')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE);

        return response()->json([
            'data' => NotificationResource::collection($paginator->items())->resolve(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
        ]);
    }
}
