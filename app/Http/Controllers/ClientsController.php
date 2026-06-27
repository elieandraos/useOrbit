<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class ClientsController extends Controller
{
    #[Authorize('viewAny', Client::class)]
    public function index(): Response
    {
        $clients = Client::query()->paginate();

        return Inertia::render('Clients/Index', [
            'clients' => ClientResource::collection($clients),
        ]);
    }

    #[Authorize('view', 'client')]
    public function show(Client $client): Response
    {
        return Inertia::render('Clients/Show', [
            'client' => ClientResource::make($client),
        ]);
    }
}
