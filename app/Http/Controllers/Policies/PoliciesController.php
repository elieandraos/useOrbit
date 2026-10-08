<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Enums\PolicyClass;
use App\Enums\PolicyDisplayStatus;
use App\Enums\PolicySource;
use App\Enums\PolicyType;
use App\Filters\PolicyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\IndexPolicyRequest;
use App\Http\Resources\CurrencyResource;
use App\Http\Resources\PolicyResource;
use App\Models\Carrier;
use App\Models\Currency;
use App\Models\Policy;
use App\Support\Policies\PolicyEntrySelection;
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
            'statuses' => collect(PolicyDisplayStatus::all()),
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
    public function create(Request $request, PolicyFormOptions $policyFormOptions, PolicyEntrySelection $policyEntrySelection): Response
    {
        $selected = $policyEntrySelection->selected($request);

        return inertia('Policies/Create', [
            ...$policyFormOptions->shared(),
            'classes' => collect(PolicyClass::all()),
            'selected' => [
                ...$selected,
                'class' => $selected['class'] ?? PolicyClass::Medical->value,
            ],
        ]);
    }
}
