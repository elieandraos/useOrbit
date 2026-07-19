<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\UnarchiveClientAction;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class ClientsUnarchiveController extends Controller
{
    #[Authorize('unarchive', 'client')]
    public function __invoke(Request $request, Client $client, UnarchiveClientAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $client);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client unarchived.')]);

        return to_route('clients.index');
    }
}
