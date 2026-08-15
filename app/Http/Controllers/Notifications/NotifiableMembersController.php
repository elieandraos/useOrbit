<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class NotifiableMembersController extends Controller
{
    #[Authorize('viewAny', User::class)]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $members = User::query()
            ->activeInCurrentOrganization()
            ->whereKeyNot($actor->id)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

        return response()->json(['data' => $members]);
    }
}
