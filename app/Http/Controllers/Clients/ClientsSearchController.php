<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Enums\ClientStatus;
use App\Filters\ClientFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\SearchClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class ClientsSearchController extends Controller
{
    /**
     * The first ten active clients matching the search, ordered by their displayed name.
     */
    #[Authorize('viewAny', Client::class)]
    public function __invoke(SearchClientRequest $request): JsonResponse
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $clients = Client::query()
            ->where('status', ClientStatus::Active)
            ->filter(new ClientFilter(['search' => $request->validated('search')]))
            ->inDisplayedNameOrder()
            ->limit(10)
            ->get(['id', 'client_type', 'company_name', 'first_name', 'last_name'])
            ->map(fn (Client $client): array => ['id' => $client->id, 'full_name' => $client->full_name]);

        return response()->json(['data' => $clients]);
    }
}
