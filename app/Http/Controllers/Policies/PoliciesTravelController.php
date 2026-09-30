<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyTravelAction;
use App\Actions\Policies\UpdatePolicyTravelAction;
use App\Enums\PolicyClass;
use App\Enums\TravelCoverageTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyTravelRequest;
use App\Http\Requests\Policies\UpdatePolicyTravelRequest;
use App\Http\Resources\PolicyTravelResource;
use App\Models\Policy;
use App\Models\User;
use App\Support\Policies\PolicyFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesTravelController extends Controller
{
    public function __construct(private readonly PolicyFormOptions $policyFormOptions) {}

    #[Authorize('create', Policy::class)]
    public function create(): Response
    {
        return inertia('PolicyTravel/Create', $this->formOptions());
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyTravelRequest $request, CreatePolicyTravelAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.travel.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent', 'travelDetails']);

        return inertia('PolicyTravel/Show', [
            'policy' => PolicyTravelResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent', 'travelDetails']);

        return inertia('PolicyTravel/Edit', [
            'policy' => PolicyTravelResource::make($policy),
            ...$this->formOptions($policy),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyTravelRequest $request, Policy $policy, UpdatePolicyTravelAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.travel.show', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?Policy $policy = null): array
    {
        return [
            ...$this->policyFormOptions->shared($policy),
            'subclasses' => PolicyClass::Travel->subclasses(),
            'coverageTiers' => collect(TravelCoverageTier::all()),
        ];
    }
}
