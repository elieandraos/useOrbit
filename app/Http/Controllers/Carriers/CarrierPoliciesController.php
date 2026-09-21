<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\PolicyResource;
use App\Models\Carrier;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class CarrierPoliciesController extends Controller
{
    #[Authorize('view', 'carrier')]
    public function index(Carrier $carrier): Response
    {
        $policies = $carrier->policies()
            ->with(['client', 'carrier'])
            ->latest('effective_date')
            ->orderBy('id')
            ->paginate(7)
            ->withQueryString();

        return inertia('CarrierPolicies/Index', [
            'carrier' => CarrierResource::make($carrier),
            'policies' => PolicyResource::collection($policies),
            'policiesCount' => $policies->total(),
        ]);
    }
}
