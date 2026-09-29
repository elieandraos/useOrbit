<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Filters\PolicyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\IndexPolicyRequest;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\PolicyResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use App\Support\Policies\PolicyFormOptions;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class PoliciesController extends Controller
{
    #[Authorize('viewAny', Policy::class)]
    public function index(IndexPolicyRequest $request): Response
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $policies = Policy::query()
            ->with(['client', 'carrier'])
            ->filter(new PolicyFilter($request->validated()))
            ->latest('effective_date')
            ->orderBy('id')
            ->paginate(7)
            ->withQueryString();

        return inertia('Policies/Index', [
            'policies' => PolicyResource::collection($policies),
            'statuses' => collect(PolicyStatus::all()),
            'types' => collect(PolicyType::all()),
            'classes' => collect(PolicyClass::all()),
            'sources' => collect(PolicySource::all()),
            'carriers' => CarrierResource::collection(Carrier::query()->orderBy('name')->get()),
            'filters' => [
                'search' => $request->validated('search'),
                'status' => $request->validated('status'),
                'type' => $request->validated('type'),
                'class' => $request->validated('class'),
                'carrier_id' => $request->validated('carrier_id'),
                'source' => $request->validated('source'),
                'effective_from' => $request->validated('effective_from'),
                'effective_to' => $request->validated('effective_to'),
                'amount_min' => $request->validated('amount_min'),
                'amount_max' => $request->validated('amount_max'),
            ],
        ]);
    }

    #[Authorize('create', Policy::class)]
    public function create(Request $request, PolicyFormOptions $policyFormOptions): Response
    {
        return inertia('Policies/Create', [
            ...$policyFormOptions->shared(),
            'classes' => collect(PolicyClass::all()),
            'selected' => [
                'class' => $this->selectedEnumValue($request, 'class', PolicyClass::class),
                'type' => $this->selectedEnumValue($request, 'type', PolicyType::class),
                'client_id' => Client::query()->find($request->integer('client_id'))?->id,
                'carrier_id' => Carrier::query()->find($request->integer('carrier_id'))?->id,
                'agent_id' => Agent::query()->find($request->integer('agent_id'))?->id,
                'status' => $this->selectedEnumValue($request, 'status', PolicyStatus::class),
                'source' => $this->selectedEnumValue($request, 'source', PolicySource::class),
            ],
        ]);
    }

    /**
     * Resolve a carried-over query value to its enum value, ignoring anything that isn't one of the enum's cases.
     *
     * @param  class-string<\BackedEnum>  $enumClass
     */
    private function selectedEnumValue(Request $request, string $key, string $enumClass): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? $enumClass::tryFrom($value)?->value : null;
    }
}
