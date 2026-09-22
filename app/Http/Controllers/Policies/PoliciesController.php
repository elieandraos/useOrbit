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
use App\Http\Resources\AgentResource;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\PolicyResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
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
    public function create(Request $request): Response
    {
        $selectedClient = Client::query()->find($request->integer('client_id'));

        return inertia('Policies/Create', [
            'clients' => ClientResource::collection(Client::query()->orderBy('id')->get()),
            'carriers' => CarrierResource::collection(Carrier::query()->orderBy('name')->get()),
            'agents' => AgentResource::collection(Agent::query()->orderBy('id')->get()),
            'classes' => collect(PolicyClass::all()),
            'types' => collect(PolicyType::all()),
            'statuses' => collect(PolicyStatus::all()),
            'sources' => collect(PolicySource::all()),
            'selectedClientId' => $selectedClient?->id,
        ]);
    }
}
