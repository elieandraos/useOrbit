<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\CreateAgentAction;
use App\Actions\Agents\DestroyAgentAction;
use App\Actions\Agents\UpdateAgentAction;
use App\Enums\PolicyStatus;
use App\Filters\AgentFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agents\IndexAgentRequest;
use App\Http\Requests\Agents\StoreAgentRequest;
use App\Http\Requests\Agents\UpdateAgentRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\PolicyResource;
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
    /**
     * How many days ahead of its expiry a policy is surfaced as "renewing soon" on the agent's
     * overview — a read-time concept derived from `expiry_date`, never stored.
     */
    private const int RENEWING_SOON_WINDOW_DAYS = 30;

    /**
     * How many of the agent's soonest-to-expire renewing policies the overview card shows.
     */
    private const int RENEWING_SOON_LIMIT = 5;

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

        $renewingPolicies = $agent->policies()
            ->with(['client', 'carrier'])
            ->where('status', PolicyStatus::Active->value)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays(self::RENEWING_SOON_WINDOW_DAYS)->toDateString())
            ->orderBy('expiry_date')
            ->limit(self::RENEWING_SOON_LIMIT)
            ->get();

        return inertia('Agents/Show', [
            'agent' => AgentResource::make($agent),
            'policiesCount' => $agent->policies()->count(),
            'renewingPolicies' => PolicyResource::collection($renewingPolicies),
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
