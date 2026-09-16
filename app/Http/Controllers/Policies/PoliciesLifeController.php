<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyLifeAction;
use App\Actions\Policies\UpdatePolicyLifeAction;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyLifeRequest;
use App\Http\Requests\Policies\UpdatePolicyLifeRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\PolicyLifeResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesLifeController extends Controller
{
    #[Authorize('create', Policy::class)]
    public function create(): Response
    {
        return inertia('PolicyLife/Create', $this->formOptions());
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyLifeRequest $request, CreatePolicyLifeAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.life.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Life, 404);

        $policy->load(['client', 'carrier', 'agent', 'lifeDetails']);

        return inertia('PolicyLife/Show', [
            'policy' => PolicyLifeResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Life, 404);

        $policy->load(['client', 'carrier', 'agent', 'lifeDetails']);

        return inertia('PolicyLife/Edit', [
            'policy' => PolicyLifeResource::make($policy),
            ...$this->formOptions(),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyLifeRequest $request, Policy $policy, UpdatePolicyLifeAction $action): RedirectResponse
    {
        abort_unless($policy->class === PolicyClass::Life, 404);

        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.life.show', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'clients' => ClientResource::collection(Client::query()->orderBy('id')->get()),
            'carriers' => CarrierResource::collection(Carrier::query()->orderBy('name')->get()),
            'agents' => AgentResource::collection(Agent::query()->orderBy('id')->get()),
            'types' => collect(PolicyType::all()),
            'statuses' => collect(PolicyStatus::all()),
            'sources' => collect(PolicySource::all()),
        ];
    }
}
