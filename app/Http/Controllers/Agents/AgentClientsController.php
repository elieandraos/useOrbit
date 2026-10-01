<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\ClientResource;
use App\Models\Agent;
use App\Models\Client;
use App\Sorts\ClientSort;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class AgentClientsController extends Controller
{
    #[Authorize('view', 'agent')]
    public function __invoke(Agent $agent): Response
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $clients = Client::query()
            ->whereIn('clients.id', $agent->clients()->select('clients.id'))
            ->sort(new ClientSort('name', 'asc'))
            ->paginate(7)
            ->withQueryString();

        return inertia('AgentClients/Index', [
            'agent' => AgentResource::make($agent),
            'policiesCount' => $agent->policies()->count(),
            'clients' => ClientResource::collection($clients),
        ]);
    }
}
