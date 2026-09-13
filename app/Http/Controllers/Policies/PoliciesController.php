<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyAction;
use App\Actions\Policies\UpdatePolicyAction;
use App\Enums\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyRequest;
use App\Http\Requests\Policies\UpdatePolicyRequest;
use App\Http\Resources\PolicyResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesController extends Controller
{
    #[Authorize('viewAny', Policy::class)]
    public function index(): Response
    {
        $policies = Policy::query()
            ->with(['client', 'carrier'])
            ->latest('effective_date')
            ->orderBy('id')
            ->paginate(7)
            ->withQueryString();

        return inertia('Policies/Index', [
            'policies' => PolicyResource::collection($policies),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyRequest $request, CreatePolicyAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        $policy->load(['client', 'carrier', 'agent', $policy->class->detailsRelation()]);

        if ($policy->type === PolicyType::Group) {
            $policy->load('insureds');
        }

        return inertia('Policies/Show', [
            'policy' => PolicyResource::make($policy),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyRequest $request, Policy $policy, UpdatePolicyAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.show', $policy);
    }
}
