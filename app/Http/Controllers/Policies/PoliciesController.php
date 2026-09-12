<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Http\Controllers\Controller;
use App\Http\Resources\PolicyResource;
use App\Models\Policy;
use Illuminate\Routing\Attributes\Controllers\Authorize;
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
}
