<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyFireAction;
use App\Actions\Policies\UpdatePolicyFireAction;
use App\Enums\PolicyClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyFireRequest;
use App\Http\Requests\Policies\UpdatePolicyFireRequest;
use App\Http\Resources\CountryResource;
use App\Http\Resources\PolicyFireResource;
use App\Models\Country;
use App\Models\Policy;
use App\Models\User;
use App\Support\Policies\PolicyFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesFireController extends Controller
{
    public function __construct(private readonly PolicyFormOptions $policyFormOptions) {}

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
        $policy->load(['client', 'carrier', 'agent', 'currency', 'fireDetails.state', 'fireDetails.country']);

        return inertia('PolicyFire/Show', [
            'policy' => PolicyFireResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent', 'currency', 'fireDetails.state', 'fireDetails.country']);

        return inertia('PolicyFire/Edit', [
            'policy' => PolicyFireResource::make($policy),
            ...$this->formOptions($policy),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyFireRequest $request, Policy $policy, UpdatePolicyFireAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.fire.show', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?Policy $policy = null): array
    {
        return [
            ...$this->policyFormOptions->shared($policy),
            'subclasses' => PolicyClass::Fire->subclasses(),
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ];
    }
}
