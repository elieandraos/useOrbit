<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyAutomotiveAction;
use App\Actions\Policies\UpdatePolicyAutomotiveAction;
use App\Enums\PolicyClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyAutomotiveRequest;
use App\Http\Requests\Policies\UpdatePolicyAutomotiveRequest;
use App\Http\Resources\PolicyAutomotiveResource;
use App\Models\Policy;
use App\Models\User;
use App\Support\Policies\PolicyFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesAutomotiveController extends Controller
{
    public function __construct(private readonly PolicyFormOptions $policyFormOptions) {}

    #[Authorize('create', Policy::class)]
    public function create(): Response
    {
        return inertia('PolicyAutomotive/Create', $this->formOptions());
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyAutomotiveRequest $request, CreatePolicyAutomotiveAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.automotive.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency', 'automotiveDetails']);

        return inertia('PolicyAutomotive/Show', [
            'policy' => PolicyAutomotiveResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency', 'automotiveDetails']);

        return inertia('PolicyAutomotive/Edit', [
            'policy' => PolicyAutomotiveResource::make($policy),
            ...$this->formOptions($policy),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyAutomotiveRequest $request, Policy $policy, UpdatePolicyAutomotiveAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.automotive.show', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?Policy $policy = null): array
    {
        return [
            ...$this->policyFormOptions->shared($policy),
            'subclasses' => PolicyClass::Automotive->subclasses(),
        ];
    }
}
