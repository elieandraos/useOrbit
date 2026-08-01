<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class CarriersController extends Controller
{
    #[Authorize('viewAny', Carrier::class)]
    public function index(): Response
    {
        $carriers = Carrier::query()
            ->with('hqBranch')
            ->latest('onboarded_date')
            ->paginate(7)
            ->withQueryString();

        return inertia('Carriers/Index', [
            'carriers' => CarrierResource::collection($carriers),
        ]);
    }
}