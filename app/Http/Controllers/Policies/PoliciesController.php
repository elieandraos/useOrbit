<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Enums\AgentStatus;
use App\Enums\CarrierStatus;
use App\Enums\ClientStatus;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Filters\PolicyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\IndexPolicyRequest;
use App\Http\Resources\CurrencyResource;
use App\Http\Resources\PolicyResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Currency;
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
            ->with(['client', 'carrier', 'currency'])
            ->filter(new PolicyFilter($request->validated()))
            ->inListOrder()
            ->paginate(7)
            ->withQueryString();

        return inertia('Policies/Index', [
            'policies' => PolicyResource::collection($policies),
            'statuses' => collect(PolicyStatus::all()),
            'types' => collect(PolicyType::all()),
            'classes' => collect(PolicyClass::all()),
            'sources' => collect(PolicySource::all()),
            'carriers' => Carrier::query()->orderBy('name')->get()->map(fn (Carrier $carrier): array => ['id' => $carrier->id, 'name' => $carrier->name]),
            'currencies' => CurrencyResource::collection(Currency::query()->orderBy('code')->get()),
            'filters' => [
                'search' => $request->validated('search'),
                'status' => $request->validated('status'),
                'type' => $request->validated('type'),
                'class' => $request->validated('class'),
                'carrier_id' => $request->validated('carrier_id'),
                'source' => $request->validated('source'),
                'currency_id' => $request->validated('currency_id'),
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
                'client_id' => Client::query()->where('status', ClientStatus::Active)->find($this->selectedId($request, 'client_id'))?->id,
                'carrier_id' => Carrier::query()->where('status', CarrierStatus::Active)->find($this->selectedId($request, 'carrier_id'))?->id,
                'agent_id' => Agent::query()->where('status', AgentStatus::Active)->find($this->selectedId($request, 'agent_id'))?->id,
                'status' => $this->selectedEnumValue($request, 'status', PolicyStatus::class),
                'source' => $this->selectedEnumValue($request, 'source', PolicySource::class),
            ],
        ]);
    }

    /**
     * Resolve a carried-over query value to an ID, ignoring anything that isn't a single value.
     */
    private function selectedId(Request $request, string $key): ?int
    {
        return is_string($request->query($key)) ? $request->integer($key) : null;
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
