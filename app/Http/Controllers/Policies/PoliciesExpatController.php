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
use App\Support\Policies\PolicyEntrySelection;
use App\Support\Policies\PolicyFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesExpatController extends Controller
{
    public function __construct(private readonly PolicyFormOptions $policyFormOptions) {}

    #[Authorize('create', Policy::class)]
    public function create(Request $request, PolicyEntrySelection $policyEntrySelection): Response|RedirectResponse
    {
        $entry = $policyEntrySelection->summary($request, PolicyClass::Expat);

        if ($entry === null) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Some of your choices are missing or no longer available.')]);

            return to_route('policies.create', $policyEntrySelection->backToEntryQuery($request, PolicyClass::Expat));
        }

        return inertia('PolicyExpat/Create', [
            ...$this->formOptions(),
            'entry' => $entry,
        ]);
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
        Inertia::clearHistory();

        return to_route('policies.expat.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency', 'expatDetails.country']);

        return inertia('PolicyExpat/Show', [
            'policy' => PolicyExpatResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency', 'expatDetails.country']);

        return inertia('PolicyExpat/Edit', [
            'policy' => PolicyExpatResource::make($policy),
            ...$this->formOptions($policy),
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
    private function formOptions(?Policy $policy = null): array
    {
        return [
            ...$this->policyFormOptions->shared($policy),
            'subclasses' => PolicyClass::Expat->subclasses(),
            'coverageZones' => collect(ExpatCoverageZone::all()),
            'genders' => collect(Gender::all()),
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ];
    }
}
