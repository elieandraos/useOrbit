<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\CreatePolicyMedicalAction;
use App\Actions\Policies\UpdatePolicyMedicalAction;
use App\Enums\PolicyClass;
use App\Enums\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\StorePolicyMedicalRequest;
use App\Http\Requests\Policies\UpdatePolicyMedicalRequest;
use App\Http\Resources\PolicyMedicalResource;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class PoliciesMedicalController extends Controller
{
    #[Authorize('create', Policy::class)]
    public function create(): Response
    {
        return inertia('PolicyMedical/Create');
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Policy::class)]
    public function store(StorePolicyMedicalRequest $request, CreatePolicyMedicalAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy created.')]);

        return to_route('policies.medical.show', $policy);
    }

    #[Authorize('view', 'policy')]
    public function show(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Medical, 404);

        $policy->load(['client', 'carrier', 'agent', 'medicalDetails']);

        if ($policy->type === PolicyType::Group) {
            $policy->load('insureds');
        }

        return inertia('PolicyMedical/Show', [
            'policy' => PolicyMedicalResource::make($policy),
        ]);
    }

    #[Authorize('update', 'policy')]
    public function edit(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Medical, 404);

        $policy->load(['client', 'carrier', 'agent', 'medicalDetails']);

        if ($policy->type === PolicyType::Group) {
            $policy->load('insureds');
        }

        return inertia('PolicyMedical/Edit', [
            'policy' => PolicyMedicalResource::make($policy),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'policy')]
    public function update(UpdatePolicyMedicalRequest $request, Policy $policy, UpdatePolicyMedicalAction $action): RedirectResponse
    {
        abort_unless($policy->class === PolicyClass::Medical, 404);

        /** @var User $user */
        $user = $request->user();
        $policy = $action->handle($user, $policy, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Policy updated.')]);

        return to_route('policies.medical.show', $policy);
    }
}
