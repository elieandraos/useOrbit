<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class ClientsController extends Controller
{
    #[Authorize('viewAny', Client::class)]
    public function index(Request $request): Response
    {
        $clients = Client::query()
            ->where('organization_id', $request->user()->current_organization_id)
            ->paginate();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }
}
