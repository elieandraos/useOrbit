<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\CreateAgentAction;
use App\Actions\Agents\DestroyAgentAction;
use App\Actions\Agents\UpdateAgentAction;
use App\Filters\AgentFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agents\IndexAgentRequest;
use App\Http\Requests\Agents\StoreAgentRequest;
use App\Http\Requests\Agents\UpdateAgentRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CountryResource;
use App\Models\Agent;
use App\Models\Country;
use App\Models\User;
use App\Sorts\AgentSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class AgentsController extends Controller
{
    #[Authorize('viewAny', Agent::class)]
    public function index(IndexAgentRequest $request): Response
    {
        $sortColumn = $request->validated('sort');

        /** @noinspection PhpUndefinedMethodInspection */
        $agents = Agent::query()
            ->filter(new AgentFilter($request->validated()))
            ->sort(new AgentSort($sortColumn, $request->validated('direction')))
            ->paginate(7)
            ->withQueryString();

        return inertia('Agents/Index', [
            'agents' => AgentResource::collection($agents),
            'sort' => [
                'column' => $sortColumn ?? 'name',
                'direction' => $request->validated('direction'),
            ],
            'filters' => [
                'search' => $request->validated('search'),
                'archived' => $request->validated('archived'),
            ],
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

    #[Authorize('update', 'agent')]
    public function edit(Agent $agent): Response
    {
        $agent->load(['updatedBy', 'country', 'state']);

        return inertia('Agents/Edit', [
            'agent' => AgentResource::make($agent),
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'agent')]
    public function update(UpdateAgentRequest $request, Agent $agent, UpdateAgentAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $agent = $action->handle($user, $agent, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent updated.')]);

        return to_route('agents.show', $agent);
    }

    #[Authorize('delete', 'agent')]
    public function destroy(Agent $agent, DestroyAgentAction $action): RedirectResponse
    {
        $action->handle($agent);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent deleted.')]);

        return to_route('agents.index');
    }
}
