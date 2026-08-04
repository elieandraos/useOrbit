<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\CreateAgentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agents\StoreAgentRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CountryResource;
use App\Models\Agent;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
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

    #[Authorize('create', Agent::class)]
    public function create(): Response
    {
        return inertia('Agents/Create', [
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Agent::class)]
    public function store(StoreAgentRequest $request, CreateAgentAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $agent = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent created.')]);

        return to_route('agents.show', $agent);
    }

    #[Authorize('view', 'agent')]
    public function show(Agent $agent): Response
    {
        $agent->load(['country', 'state']);

        return inertia('Agents/Show', [
            'agent' => AgentResource::make($agent),
        ]);
    }
}
