<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class AgentsController extends Controller
{
    #[Authorize('viewAny', Agent::class)]
    public function index(): Response
    {
        $agents = Agent::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(7)
            ->withQueryString();

        return inertia('Agents/Index', [
            'agents' => AgentResource::collection($agents),
        ]);
    }
}
