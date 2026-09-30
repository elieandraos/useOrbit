<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyExpatAction;
use App\Actions\Policies\UpdatePolicyExpatAction;
use App\Enums\ExpatCoverageZone;
use App\Enums\Gender;
use App\Enums\PolicyClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyExpatRequest;
use App\Http\Requests\Policies\UpdatePolicyExpatRequest;
use App\Http\Resources\CountryResource;
use App\Http\Resources\PolicyExpatResource;
use App\Models\Country;
use App\Models\Policy;
use App\Models\User;
use App\Support\Policies\PolicyFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesExpatController extends Controller
{
    public function __construct(private readonly PolicyFormOptions $policyFormOptions) {}

    #[Authorize('create', Policy::class)]
    public function create(): Response
    {
        return inertia('PolicyExpat/Create', $this->formOptions());
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyExpatRequest $request, CreatePolicyExpatAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.expat.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent', 'expatDetails.country']);

        return inertia('PolicyExpat/Show', [
            'policy' => PolicyExpatResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent', 'expatDetails.country']);

        return inertia('PolicyExpat/Edit', [
            'policy' => PolicyExpatResource::make($policy),
            ...$this->formOptions(),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyExpatRequest $request, Policy $policy, UpdatePolicyExpatAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.expat.show', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            ...$this->policyFormOptions->shared(),
            'subclasses' => PolicyClass::Expat->subclasses(),
            'coverageZones' => collect(ExpatCoverageZone::all()),
            'genders' => collect(Gender::all()),
            'countries' => CountryResource::collection(Country::query()->inMarkets()->orderBy('name')->get()),
        ];
    }
}
