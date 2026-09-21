<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\PolicyResource;
use App\Models\Client;
use App\Models\Policy;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class ClientPoliciesController extends Controller
{
    #[Authorize('viewAny', Policy::class)]
    public function index(Client $client): Response
    {
        $policies = $client->policies()
            ->with(['client', 'carrier'])
            ->latest('effective_date')
            ->orderBy('id')
            ->paginate(7)
            ->withQueryString();

        return inertia('ClientPolicies/Index', [
            'client' => ClientResource::make($client),
            'policies' => PolicyResource::collection($policies),
        ]);
    }
}
