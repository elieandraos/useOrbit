<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyFireAction;
use App\Actions\Policies\UpdatePolicyFireAction;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyFireRequest;
use App\Http\Requests\Policies\UpdatePolicyFireRequest;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\PolicyFireResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Country;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesFireController extends Controller
{
    #[Authorize('create', Policy::class)]
    public function create(): Response
    {
        return inertia('PolicyFire/Create', $this->formOptions());
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyFireRequest $request, CreatePolicyFireAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.fire.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Fire, 404);

        $policy->load(['client', 'carrier', 'agent', 'fireDetails.state', 'fireDetails.country']);

        return inertia('PolicyFire/Show', [
            'policy' => PolicyFireResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Fire, 404);

        $policy->load(['client', 'carrier', 'agent', 'fireDetails.state', 'fireDetails.country']);

        return inertia('PolicyFire/Edit', [
            'policy' => PolicyFireResource::make($policy),
            ...$this->formOptions(),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyFireRequest $request, Policy $policy, UpdatePolicyFireAction $action): RedirectResponse
    {
        abort_unless($policy->class === PolicyClass::Fire, 404);

        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.fire.show', $policy);
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
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ];
    }
}
