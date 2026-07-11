<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Clients\ArchiveClientAction;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class ClientsArchiveController extends Controller
{
    #[Authorize('archive', 'client')]
    public function __invoke(Request $request, Client $client, ArchiveClientAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $client);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client archived.')]);

        return to_route('clients.show', $client);
    }
}
