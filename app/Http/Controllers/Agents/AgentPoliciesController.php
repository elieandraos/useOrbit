<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\PolicyResource;
use App\Models\Agent;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class AgentPoliciesController extends Controller
{
    #[Authorize('view', 'agent')]
    public function index(Agent $agent): Response
    {
        $policies = $agent->policies()
            ->with(['client', 'carrier'])
            ->latest('effective_date')
            ->orderBy('id')
            ->paginate(7)
            ->withQueryString();

        return inertia('AgentPolicies/Index', [
            'agent' => AgentResource::make($agent),
            'policiesCount' => $policies->total(),
            'policies' => PolicyResource::collection($policies),
        ]);
    }
}
